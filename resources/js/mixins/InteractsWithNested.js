import { mapProps } from '@/mixins';

import InteractsWithResource from './InteractsWithResource';

import { uid } from 'uid/single';

export default {
    mixins: [InteractsWithResource],

    props: {
        ...mapProps(['viaResourceId', 'viaRelationship', 'relationshipType']),

        field: {
            type: Object
        }
    },

    data: () => ({
        initialLoading: true,
        loading: true,
        resources: [],
        useTabs: false,
        collapsedChildren: []
    }),

    created() {
        this.useTabs = this.field.useTabs;
    },

    methods: {
        switchViewType() {
            this.useTabs = !this.useTabs;
        },

        calculateCollapsedChildren() {
            if (this.shouldCollapseChildren) {
                this.collapsedChildren = Array.from(Array(this.resources.length).keys());
            }
        },

        toggleCollapsedChildren(index) {
            const collapsedIndex = this.collapsedChildren.indexOf(index);

            if (collapsedIndex > -1) {
                this.collapsedChildren.splice(collapsedIndex, 1);
            } else {
                this.collapsedChildren.push(index);
            }
        },

        activateResource(indexOrString) {
            const number = Number(indexOrString);
            for (let x = 0; x < this.resources.length; x++) {
                this.resources[x].isNestedActive = isNaN(number) ? this.resources[x] == indexOrString : x === number;
            }
        },

        decorateResource(resource) {
            if (!('uid' in resource)) {
                resource.uid = uid(13);
            }

            return resource;
        }
    },

    computed: {
        decoratedResources: {
            get() {
                return this.resources;
            },
            set(value) {
                this.resources = value.map((resource, index) => this.decorateResource(resource));
            }
        },

        /**
         * Return the heading for the view
         */
        headingTitle() {
            return this.field.name;
        },

        /**
         * Get the singular name for the resource
         */
        singularName() {
            return _.capitalize(this.field.singularLabel);
        },

        authorizedToCreateNested() {
            return this.field.authorizedToCreateNested;
        },

        primaryKeyName() {
            return this.field.primaryKeyName;
        },

        hasSoftDelete() {
            return this.field.hasNestedSoftDelete;
        },

        isPanelView() {
            return !this.useTabs;
        },

        canChangeViewType() {
            return this.field.canChangeViewType;
        },

        shouldCollapseChildren() {
            return this.field.collapsedChildrenByDefault;
        },

        hiddenFields() {
            return this.field.hiddenFields;
        },

        propagated() {
            return this.field.propagated;
        },

        nestedPropagated() {
            return Object.keys(this.propagated ?? {}).reduce((carry, key) => {
                carry[`nestedPropagated[${key}]`] = this.propagated[key];

                return carry;
            }, {});
        }
    }
};
