import { describe, it, expect, afterEach, beforeEach } from 'vitest';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import LessonTutorPanel from '../LessonTutorPanel.vue';
import { STORAGE_KEYS } from '@/lib/constants';

const conversation = {
    id: 2,
    can_post: true,
    turns: [
        { id: 7, role: 'learner' as const, body: 'apa itu loop', created_at: null },
        { id: 8, role: 'tutor' as const, body: 'Loop adalah salah satu dari tiga bagian agen.', created_at: null },
    ],
};

/**
 * The overlay teleports to `<body>` to escape the Lesson's clipping `<main>`,
 * so the stub keeps it inside the wrapper where the assertions can reach it.
 */
const mountPanel = async (overrides: Partial<typeof conversation> = {}) => {
    const wrapper = mount(LessonTutorPanel, {
        props: { courseId: 1, lessonId: 14, conversation: { ...conversation, ...overrides } },
        global: { stubs: { teleport: true } },
    });

    await flushPromises();

    return wrapper;
};

const openOnLesson = (lessonId: number) => {
    sessionStorage.setItem(`${STORAGE_KEYS.tutorOpen}-${lessonId}`, '1');
};

describe('LessonTutorPanel', () => {
    beforeEach(() => {
        sessionStorage.clear();
        localStorage.clear();
    });

    afterEach(() => {
        window.innerWidth = 1024;
    });

    it('starts closed so the lesson is not covered', async () => {
        const wrapper = await mountPanel();

        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
        expect(wrapper.get('[aria-label="Buka Tutor"]').isVisible()).toBe(true);
    });

    /**
     * CONTEXT.md forbids `chatbot`, `copilot` and `assistant` for the Tutor --
     * it is the teacher a Learner talks to about a Lesson. A bare speech
     * bubble with no label is the chatbot glyph, so the mark carries a spark
     * and the control keeps its word.
     */
    it('names the Tutor on its trigger and marks it as more than a chat bubble', async () => {
        const wrapper = await mountPanel();

        const launcher = wrapper.get('[aria-label="Buka Tutor"]');

        expect(launcher.classes()).toContain('tutor-launch');
        expect(launcher.text()).toContain('Tutor');
        expect(launcher.find('.tutor-launch__spark').exists()).toBe(true);
    });

    it('reopens after a remount when the learner had the Tutor open', async () => {
        openOnLesson(14);

        const wrapper = await mountPanel();

        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('apa itu loop');
        expect(wrapper.text()).toContain('Loop adalah salah satu dari tiga bagian agen.');
    });

    it('restores the size and position the learner left behind', async () => {
        openOnLesson(14);
        localStorage.setItem(
            STORAGE_KEYS.tutorGeometry,
            JSON.stringify({ left: 120, top: 80, width: 400, height: 360 }),
        );

        const wrapper = await mountPanel();

        expect(wrapper.get('[role="dialog"]').attributes('style')).toContain('left: 120px');
        expect(wrapper.get('[role="dialog"]').attributes('style')).toContain('width: 400px');
    });

    /**
     * Geometry outlives the Lesson, so it also outlives the monitor it was
     * saved on. A window restored past the right edge has to come back within
     * reach or the Learner can never grab its header again.
     */
    it('drags a window remembered from a wider screen back into view', async () => {
        openOnLesson(14);
        localStorage.setItem(
            STORAGE_KEYS.tutorGeometry,
            JSON.stringify({ left: 5000, top: 4000, width: 400, height: 360 }),
        );

        const wrapper = await mountPanel();
        const style = wrapper.get('[role="dialog"]').attributes('style') ?? '';

        const left = Number(style.match(/left: (-?\d+)px/)?.[1]);
        const top = Number(style.match(/top: (-?\d+)px/)?.[1]);

        expect(left).toBeLessThanOrEqual(window.innerWidth - 96);
        expect(top).toBeLessThanOrEqual(window.innerHeight - 48);

        // The rescue is painted, not recorded: the wider monitor still has a
        // position waiting for the next time the learner sits at it.
        expect(JSON.parse(localStorage.getItem(STORAGE_KEYS.tutorGeometry) as string).left).toBe(5000);
    });

    it('pulls the window back into a narrowed viewport without forgetting where it was parked', async () => {
        openOnLesson(14);
        localStorage.setItem(
            STORAGE_KEYS.tutorGeometry,
            JSON.stringify({ left: 600, top: 200, width: 400, height: 360 }),
        );

        const wrapper = await mountPanel();
        expect(wrapper.get('[role="dialog"]').attributes('style')).toContain('left: 600px');

        window.innerWidth = 500;
        window.dispatchEvent(new Event('resize'));
        await flushPromises();

        expect(wrapper.get('[role="dialog"]').attributes('style')).toContain('left: 404px');
        expect(JSON.parse(localStorage.getItem(STORAGE_KEYS.tutorGeometry) as string).left).toBe(600);
    });

    it('moves the window when the header is dragged, and remembers where it landed', async () => {
        openOnLesson(14);
        localStorage.setItem(
            STORAGE_KEYS.tutorGeometry,
            JSON.stringify({ left: 600, top: 200, width: 400, height: 360 }),
        );

        const wrapper = await mountPanel();

        await wrapper.get('header').trigger('pointerdown', { clientX: 700, clientY: 230, button: 0 });
        document.dispatchEvent(new MouseEvent('pointermove', { clientX: 300, clientY: 400 }));
        await flushPromises();

        expect(wrapper.get('[role="dialog"]').attributes('style')).toContain('left: 200px');
        expect(wrapper.get('[role="dialog"]').attributes('style')).toContain('top: 370px');

        document.dispatchEvent(new MouseEvent('pointerup'));
        await flushPromises();

        expect(JSON.parse(localStorage.getItem(STORAGE_KEYS.tutorGeometry) as string)).toMatchObject({
            left: 200,
            top: 370,
        });
    });

    const dragHeaderTo = async (wrapper: VueWrapper, x: number, y: number) => {
        await wrapper.get('header').trigger('pointerdown', { clientX: 700, clientY: 230, button: 0 });
        document.dispatchEvent(new MouseEvent('pointermove', { clientX: x, clientY: y }));
        await flushPromises();
    };

    const releaseAt = async (x: number, y: number) => {
        document.dispatchEvent(new MouseEvent('pointerup', { clientX: x, clientY: y }));
        await flushPromises();
    };

    /**
     * Dragging to an edge is the only way most learners will ever discover
     * docking, so the hint has to show before the pointer is released and the
     * release has to honour it.
     */
    it('previews a dock while the drag is near an edge, then docks on release', async () => {
        openOnLesson(14);

        const wrapper = await mountPanel();

        await dragHeaderTo(wrapper, 6, 300);

        expect(wrapper.get('.tutor-snap-hint[data-side="left"]').attributes('data-active')).toBe('true');
        expect(wrapper.get('.tutor-snap-hint[data-side="right"]').attributes('data-active')).toBe('false');

        await releaseAt(6, 300);

        const dialog = wrapper.get('[role="dialog"]');

        expect(dialog.attributes('data-mode')).toBe('dock-left');
        expect(JSON.parse(localStorage.getItem(STORAGE_KEYS.tutorGeometry) as string).mode).toBe('dock-left');

        // CSS pins a docked window; emitting left/top here would only give the
        // stylesheet something to fight.
        expect(dialog.attributes('style')).not.toContain('left:');
        expect(dialog.attributes('style')).not.toContain('height:');
    });

    it('docks from the keyboard and refuses to nudge what is pinned', async () => {
        openOnLesson(14);

        const wrapper = await mountPanel();
        const header = wrapper.get('header');

        await header.trigger('keydown', { key: 'End' });

        const dialog = wrapper.get('[role="dialog"]');
        expect(dialog.attributes('data-mode')).toBe('dock-right');

        const pinned = dialog.attributes('style');
        await header.trigger('keydown', { key: 'ArrowLeft' });

        expect(wrapper.get('[role="dialog"]').attributes('style')).toBe(pinned);
    });

    /**
     * Escape is the key a learner reaches for to get the Lesson back. Closing
     * on it would be the one action here they cannot undo with the same key.
     */
    it('collapses on Escape rather than closing the conversation', async () => {
        openOnLesson(14);

        const wrapper = await mountPanel();

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await flushPromises();

        const dialog = wrapper.get('[role="dialog"]');

        expect(dialog.attributes('data-collapsed')).toBe('true');
        expect(dialog.exists()).toBe(true);
        expect(wrapper.get('header').exists()).toBe(true);
    });

    /**
     * A draggable, resizable window on a phone is a bad joke, so below the
     * breakpoint the same markup is a sheet: no dock control, a scrim behind
     * it, and the header carrying the one gesture a phone user already knows.
     */
    describe('on a phone', () => {
        const realMatchMedia = window.matchMedia;
        const realRect = Element.prototype.getBoundingClientRect;

        beforeEach(() => {
            window.matchMedia = ((query: string) => ({
                matches: query.includes('640'),
                media: query,
                onchange: null,
                addEventListener: () => {},
                removeEventListener: () => {},
                addListener: () => {},
                removeListener: () => {},
                dispatchEvent: () => true,
            })) as unknown as typeof window.matchMedia;
        });

        afterEach(() => {
            window.matchMedia = realMatchMedia;
            Element.prototype.getBoundingClientRect = realRect;
        });

        it('becomes a sheet with a scrim and no dock control', async () => {
            openOnLesson(14);

            const wrapper = await mountPanel();

            expect(wrapper.find('[aria-label="Tempelkan Tutor ke tepi"]').exists()).toBe(false);
            expect(wrapper.get('header').attributes('aria-label')).toContain('Tarik ke bawah');
            expect(wrapper.find('.bg-\\[var\\(--tutor-scrim\\)\\]').exists()).toBe(true);
        });

        it('closes when the sheet is pulled past a quarter of its height, and not before', async () => {
            openOnLesson(14);
            Element.prototype.getBoundingClientRect = function () {
                return { height: 400, width: 380, top: 0, left: 0, right: 380, bottom: 400, x: 0, y: 0, toJSON: () => ({}) };
            };

            const wrapper = await mountPanel();

            await wrapper.get('header').trigger('pointerdown', { clientX: 190, clientY: 10, button: 0 });
            document.dispatchEvent(new MouseEvent('pointermove', { clientX: 190, clientY: 70 }));
            document.dispatchEvent(new MouseEvent('pointerup', { clientX: 190, clientY: 70 }));
            await flushPromises();

            expect(wrapper.find('[role="dialog"]').exists()).toBe(true);

            await wrapper.get('header').trigger('pointerdown', { clientX: 190, clientY: 10, button: 0 });
            document.dispatchEvent(new MouseEvent('pointermove', { clientX: 190, clientY: 260 }));
            document.dispatchEvent(new MouseEvent('pointerup', { clientX: 190, clientY: 260 }));
            await flushPromises();

            expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
        });
    });

    it('offers no composer on a conversation that cannot be added to', async () => {
        openOnLesson(14);

        const wrapper = await mountPanel({ can_post: false });

        expect(wrapper.find('form').exists()).toBe(false);
        expect(wrapper.text()).toContain('Percakapan ini tidak dapat ditambah.');
    });
});
