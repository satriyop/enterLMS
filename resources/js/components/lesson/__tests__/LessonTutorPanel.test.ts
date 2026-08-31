import { describe, it, expect, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
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
        expect(JSON.parse(localStorage.getItem(STORAGE_KEYS.tutorGeometry) as string).left).toBe(left);
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

    it('offers no composer on a conversation that cannot be added to', async () => {
        openOnLesson(14);

        const wrapper = await mountPanel({ can_post: false });

        expect(wrapper.find('form').exists()).toBe(false);
        expect(wrapper.text()).toContain('Percakapan ini tidak dapat ditambah.');
    });
});
