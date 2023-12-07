import { mapProps } from '@/mixins';

export default {
    props: {
        ...mapProps(['resourceName', 'viaResource']),
    },

    methods: {
        uniqueResourceKey(resource) {
            return `${this.resourceName}-${this.viaResource}-${resource.uid}`;
        },
    },
};
