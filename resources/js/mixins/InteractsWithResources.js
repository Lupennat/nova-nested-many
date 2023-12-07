import InteractsWithResource from './InteractsWithResource';

export default {
    mixins: [InteractsWithResource],

    emits: ['activate-resource', 'toggle-collapsed-children'],

    props: {
        resources: {
            type: Array,
            required: true,
        },

        singularName: {
            type: String,
        },

        collapsedChildren: {
            type: Array,
            required: true,
        },

        hiddenFields: {
            type: Array,
            required: true,
        },
    },

    methods: {
        /**
         * Return the heading for the view
         */
        headingTitle(resource, index) {
            return resource.title || `${this.singularName}: ${index + 1}`;
        },

        activate(index) {
            this.$emit('activate-resource', index);
            // this.$emit('update-last-active', index);
        },

        /**
         * Determine if the index view should be collapsed.
         */
        isCollapsed(index) {
            return this.collapsedChildren.includes(index);
        },

        toggleCollapse(index) {
            this.$emit('toggle-collapsed-children', index);
        },
    },

    computed: {
        lastActiveIndex() {
            let index = 0;
            for (let x = 0; x < this.resources.length; x++) {
                if (this.resources[x].isNestedActive) {
                    index = x;
                }
            }

            return index;
        },
    },
};
