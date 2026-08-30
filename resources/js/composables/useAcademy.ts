import type {
    AcademyFeature,
    AcademyLabel,
    AcademyShared,
} from '@/types/academy';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAcademy() {
    const page = usePage();

    const academy = computed(() => page.props.academy as AcademyShared);

    function enabled(feature: AcademyFeature): boolean {
        return academy.value.features[feature];
    }

    function label(key: AcademyLabel): string {
        return academy.value.labels[key];
    }

    return {
        features: computed(() => academy.value.features),
        labels: computed(() => academy.value.labels),
        identity: computed(() => academy.value.identity),
        enabled,
        label,
    };
}
