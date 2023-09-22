<template>
    <LoadingView :loading="initialLoading">
        <div>
            <Heading :level="1" class="mb-3 flex items-center">
                <BasicButton type="button" class="px-0 mr-3" v-if="canChangeViewType" @click="switchViewType">
                    <Icon
                        :type="!isPanelView ? 'view-list' : 'view-grid'"
                        :title="!isPanelView ? 'view as panels' : 'view as tabs'"
                    />
                </BasicButton>
                <span v-html="headingTitle" />
                <button
                    v-if="!loading"
                    @click="handleCollapsableChange"
                    class="rounded border border-transparent h-6 w-6 ml-1 inline-flex items-center justify-center focus:outline-none focus:ring ring-primary-200"
                    :aria-label="__('Toggle Collapsed')"
                    :aria-expanded="shouldBeCollapsed === false ? 'true' : 'false'"
                >
                    <CollapseButton :collapsed="shouldBeCollapsed" />
                </button>
            </Heading>

            <template v-if="!shouldBeCollapsed">
                <div class="flex"></div>

                <div>
                    <LoadingView :loading="loading">
                        <IndexErrorDialog
                            v-if="resourceResponseError != null"
                            :resource="resourceInformation"
                            @click="getResources"
                        />

                        <template v-else>
                            <component
                                :is="resolveComponentName"
                                :hidden-fields="hiddenFields"
                                :resources="resources"
                                :resource-name="resourceName"
                                :via-resource="viaResource"
                                :collapsed-children="collapsedChildren"
                                :singular-name="singularName"
                                @activate-resource="activateResource"
                                @toggle-collapsed-children="toggleCollapsedChildren"
                                @actionExecuted="actionExecuted"
                            />
                        </template>
                    </LoadingView>
                </div>
            </template>
        </div>
    </LoadingView>
</template>

<script>
    import { CancelToken, isCancel } from 'axios';

    import DetailPanelsNestedResource from '../components/panels/DetailPanelsNestedResource';
    import DetailTabsNestedResource from '../components/tabs/DetailTabsNestedResource';

    import { Collapsable, InteractsWithResourceInformation } from '@/mixins';
    import InteractsWithNested from '../mixins/InteractsWithNested';

    export default {
        name: 'ResourceDetailNested',

        emits: ['actionExecuted'],

        mixins: [InteractsWithNested, Collapsable, InteractsWithResourceInformation],

        components: { DetailPanelsNestedResource, DetailTabsNestedResource },

        data: () => ({
            canceller: null,
            resourceResponse: null,
            resourceResponseError: null
        }),

        async created() {
            if (Nova.missingResource(this.resourceName)) {
                return Nova.visit('/404');
            }

            await this.getResources();
            this.calculateCollapsedChildren();

            this.initialLoading = false;
        },

        beforeUnmount() {
            if (this.canceller !== null) this.canceller();
        },

        methods: {
            async handleCollapsableChange() {
                this.loading = true;

                this.toggleCollapse();

                if (!this.collapsed) {
                    await this.getResources();
                } else {
                    this.loading = false;
                }
            },

            /**
             * Get the resources based on the current page, search, filters, etc.
             */
            async getResources() {
                if (this.shouldBeCollapsed) {
                    this.loading = false;
                    return;
                }

                this.loading = true;
                this.resourceResponseError = null;

                try {
                    const data = (
                        await Nova.request().get(
                            '/nova-vendor/nested-many/' + this.resourceName + '/detail-resources',
                            {
                                params: this.resourceRequestQueryString,
                                cancelToken: new CancelToken(canceller => {
                                    this.canceller = canceller;
                                })
                            }
                        )
                    ).data;

                    this.resources = [];

                    this.resources = data.resources;

                    this.activateResourceByDefault();

                    this.handleResourcesLoaded();
                } catch (e) {
                    if (isCancel(e)) {
                        return;
                    }

                    this.loading = false;
                    this.resourceResponseError = e;
                }
            },

            /**
             * Handle resources loaded event.
             */
            handleResourcesLoaded() {
                this.loading = false;

                Nova.$emit('resources-loaded', {
                    resourceName: this.resourceName,
                    mode: 'related'
                });
            },

            /**
             * Handle the actionExecuted event and pass it up the chain.
             */
            actionExecuted() {
                this.$emit('actionExecuted');
            }
        },

        computed: {
            resolveComponentName() {
                return this.isPanelView ? 'detail-panels-nested-resource' : 'detail-tabs-nested-resource';
            },

            /**
             * Determine if the index view should be collapsed.
             */
            shouldBeCollapsed() {
                return this.collapsed;
            },

            collapsedByDefault() {
                return this.field?.collapsedByDefault ?? false;
            },

            localStorageKey() {
                let name = `${name}.${this.viaRelationship}`;

                return `nova.resources.nested.${name}.collapsed`;
            },

            /**
             * Build the resource request query string.
             */
            resourceRequestQueryString() {
                return {
                    viaResource: this.viaResource,
                    viaResourceId: this.viaResourceId,
                    viaRelationship: this.viaRelationship,
                    viaResourceRelationship: this.viaResourceRelationship,
                    relationshipType: this.relationshipType,
                    ...this.nestedPropagated
                };
            }
        }
    };
</script>
