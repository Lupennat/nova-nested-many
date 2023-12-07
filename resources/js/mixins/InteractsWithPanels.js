import InteractsWithResources from './InteractsWithResources';

export default {
    mixins: [InteractsWithResources],

    data: () => ({
        highlighted: -1,
        highlightedTimeout: null,
    }),

    watch: {
        highlighted(val, oldVal) {
            if ((val > 1 && val) || oldVal) {
                this.setHighlightedTimeout();
            }
        },
    },

    created() {
        for (let x = 0; x < this.resources.length; x++) {
            if (this.resources[x].isNestedActive && this.isCollapsed(x)) {
                this.toggleCollapse(x);
            }
        }

        if (this.lastActiveIndex > 0) {
            this.$nextTick(() => {
                this.scrollResourceIntoView(this.lastActiveIndex);
                this.highlighted = this.lastActiveIndex;
            });
        }
    },

    methods: {
        keyRef(index) {
            return `${this.resourceName}-${this.viaResource}-${index}`;
        },

        isHighlighted(index) {
            return this.highlighted === index;
        },

        setHighlightedTimeout() {
            clearTimeout(this.highlightedTimeout);
            this.highlightedTimeout = null;
            this.highlightedTimeout = setTimeout(() => {
                this.highlighted = -1;
            }, 2500);
        },

        scrollResourceIntoView(index) {
            let ref = this.$refs[this.keyRef(index)];

            if (ref) {
                ref = Array.isArray(ref) ? ref[0] : ref;
                const element = ref instanceof HTMLElement ? ref : ref.$el instanceof HTMLElement ? ref.$el : null;

                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        },
    },
};
