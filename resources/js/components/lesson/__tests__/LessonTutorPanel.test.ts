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

describe('LessonTutorPanel', () => {
    beforeEach(() => {
        sessionStorage.clear();
    });

    it('starts closed so the lesson is not covered', async () => {
        const wrapper = mount(LessonTutorPanel, {
            props: { courseId: 1, lessonId: 14, conversation },
        });

        await flushPromises();

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
        const wrapper = mount(LessonTutorPanel, {
            props: { courseId: 1, lessonId: 14, conversation },
        });

        await flushPromises();

        const launcher = wrapper.get('[aria-label="Buka Tutor"]');

        expect(launcher.classes()).toContain('tutor-launch');
        expect(launcher.text()).toContain('Tutor');
        expect(launcher.find('.tutor-launch__spark').exists()).toBe(true);
    });

    it('reopens after a remount when the learner had the Tutor open', async () => {
        sessionStorage.setItem(`${STORAGE_KEYS.tutorOpen}-14`, '1');

        const wrapper = mount(LessonTutorPanel, {
            props: { courseId: 1, lessonId: 14, conversation },
        });

        await flushPromises();

        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('apa itu loop');
        expect(wrapper.text()).toContain('Loop adalah salah satu dari tiga bagian agen.');
    });
});
