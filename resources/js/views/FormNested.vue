<template>
    <LoadingView class="nested-many-form" :loading="initialLoading" v-show="isVisible">
        <div>
            <Heading
                :level="1"
                :class="[hasHelpText || hasError ? 'mb-2' : 'mb-3', hasError ? 'text-red-500' : '']"
                class="mb-3 md:flex items-center"
            >
                <div class="flex flex-wrap items-center">
                    <BasicButton
                        type="button"
                        class="px-0 mr-3"
                        v-if="canChangeViewType"
                        @click="switchViewType"
                        :disabled="loading"
                    >
                        <Icon
                            :type="!isPanelView ? 'view-list' : 'view-grid'"
                            :title="!isPanelView ? 'view as panels' : 'view as tabs'"
                        />
                    </BasicButton>
                    {{ headingTitle }}
                </div>
                <div v-if="availableStandaloneActions.length" class="ml-auto flex items-center">
                    <div class="hidden md:flex items-center flex-1">
                        <div class="h-9 ml-auto flex items-center pr-2 md:pr-3">
                            <div class="flex">
                                <ActionSelector
                                    :actions="availableStandaloneActions"
                                    :working="loading || !!runningActionKey"
                                    @run-action="runAction"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Action Selector -->
                    <div class="flex items-center md:hidden w-full">
                        <ActionSelector
                            width="full"
                            :actions="availableStandaloneActions"
                            :working="loading || !!runningActionKey"
                            @run-action="runAction"
                        />
                    </div>
                </div>
            </Heading>

            <p v-if="hasHelpText" class="text-gray-500 text-sm font-semibold italic mb-3" v-html="field.helpText"></p>
            <p v-if="firstError" class="help-text-error mb-3">{{ firstError }}</p>

            <div>
                <LoadingView :loading="loading">
                    <component
                        :is="resolveComponentName"
                        :validation-key="validationKey"
                        :hidden-fields="hiddenFields"
                        :resources="decoratedResources"
                        :resource-name="resourceName"
                        :via-resource="viaResource"
                        :via-resource-id="viaResourceId"
                        :via-relationship="viaRelationship"
                        :relationship-type="relationshipType"
                        :authorized-to-create-nested="authorizedToCreateNested"
                        :is-locked="isLocked"
                        :min-children="minChildren"
                        :max-children="maxChildren"
                        :collapsed-children="collapsedChildren"
                        :singular-name="singularName"
                        :errors="nestedErrors"
                        :show-help-text="showHelpText"
                        :has-soft-delete="hasSoftDelete"
                        :primary-key-name="primaryKeyName"
                        :available-actions="availableActions"
                        :add-action="addAction"
                        :delete-action="deleteAction"
                        :restore-action="restoreAction"
                        :running-action="runningActionKey"
                        @run-action="runAction"
                        @activate-resource="activateResource"
                        @toggle-collapsed-children="toggleCollapsedChildren"
                        @field-changed="$emit('field-changed')"
                        @file-deleted="$emit('file-deleted')"
                        @file-upload-started="$emit('file-upload-started')"
                        @file-upload-finished="$emit('file-upload-finished')"
                    />
                </LoadingView>
            </div>

            <component
                v-if="confirmActionModalOpened"
                :show="confirmActionModalOpened"
                :is="selectedAction.component"
                :working="!!runningActionKey"
                :selected-resources="selectedResources"
                :resource-name="resourceName"
                :action="selectedAction"
                :endpoint="syncEndpoint"
                :errors="actionErrors"
                @confirm="executeAction"
                @close="closeConfirmationModal"
            />
        </div>
    </LoadingView>
</template>

<script>
    import FormPanelsNestedResource from '../components/panels/FormPanelsNestedResource';
    import FormTabsNestedResource from '../components/tabs/FormTabsNestedResource';
    import ActionSelector from '../components/ActionSelector';

    import { mapProps } from '@/mixins';

    import InteractsWithNested from '../mixins/InteractsWithNested';
    import NestedFormData from '../mixins/NestedFormData';
    import HandlesActions from '../mixins/HandlesActions';

    import { HandlesValidationErrors } from 'laravel-nova';
    import { Errors } from 'form-backend-validation';

    export default {
        name: 'ResourceFormNested',

        emits: ['field-changed', 'file-deleted', 'file-upload-started', 'file-upload-finished'],

        mixins: [InteractsWithNested, HandlesValidationErrors, NestedFormData, HandlesActions],

        components: { FormPanelsNestedResource, FormTabsNestedResource, ActionSelector },

        props: {
            ...mapProps(['showHelpText', 'mode']),
        },

        data: () => ({
            defaultResources: [],
            nestedErrors: new Errors(),
        }),

        watch: {
            errors(value) {
                this.nestedErrors = value;
            },
        },

        /**
         * Mount the component.
         */
        async mounted() {
            this.initialLoading = true;
            await this.initializeComponent(!this.isManagedByParent);
            this.initialLoading = false;
            this.$watch('needsReload', function (val, oldVal) {
                if (val !== oldVal) {
                    this.initializeComponent();
                }
            });
            this.$watch('defaultActive', function (val, oldVal) {
                if (val !== oldVal) {
                    this.activateResourceByDefault();
                }
            });
            this.$watch('runningActionKey', function (val) {
                if (val !== '') {
                    this.disableFormUpdate();
                } else {
                    this.enableFormUpdate();
                }
            });
        },

        methods: {
            async switchViewType() {
                this.loading = true;
                try {
                    InteractsWithNested.methods.switchViewType.call(this);
                    await this.getUpdatedResources();
                } catch (error) {
                    Nova.error(this.__('There was a problem fetching the resource.'));
                } finally {
                    this.loading = false;
                }
            },

            disableFormUpdate() {
                // this is the only way to disable update buttons
                this.$emit('file-upload-started');
            },

            enableFormUpdate() {
                // this is the only way to re-enable update buttons
                this.$emit('file-upload-finished');
            },

            async initializeComponent(loadResources = true) {
                this.loading = true;
                this.disableFormUpdate();

                try {
                    await Promise.all([
                        this.getActions(),
                        this.getDefaultResources(),
                        loadResources
                            ? this.initialLoading
                                ? this.getResources()
                                : this.getUpdatedResources()
                            : Promise.resolve(),
                    ]);

                    if (!loadResources && (this.decoratedResources.length === 0 || this.overwriteWithDefault)) {
                        this.decoratedResources = this.defaultResources.slice();
                    }

                    this.calculateCollapsedChildren();
                    this.enableFormUpdate();
                } catch (error) {
                    Nova.error(this.__('There was a problem fetching the resource.'));
                } finally {
                    this.loading = false;
                }
            },

            fill(formData, withDelete = false) {
                this.generateResourcesFormData(
                    formData,
                    this.field.attribute,
                    this.decoratedResources,
                    this.primaryKeyName,
                    withDelete,
                );
            },

            async getResources() {
                this.decoratedResources = [];

                try {
                    this.decoratedResources = (
                        await Nova.request().get('/nova-vendor/nested-many/' + this.resourceName + '/edit-resources', {
                            params: {
                                ...this.resourceRequestEditQueryString,
                                ...this.nestedPropagated,
                            },
                        })
                    ).data.resources;

                    this.activateResourceByDefault();
                } catch (error) {
                    throw error;
                }
            },

            async getUpdatedResources() {
                const formData = this.generateResourcesFormData(
                    new FormData(),
                    'nestedChildren',
                    this.decoratedResources,
                    this.primaryKeyName,
                    true,
                );

                this.decoratedResources = [];

                try {
                    this.decoratedResources = (
                        await Nova.request().post(
                            '/nova-vendor/nested-many/' + this.resourceName + '/update-resources',
                            formData,
                            {
                                params: this.resourceRequestEditQueryString,
                            },
                        )
                    ).data.resources;
                } catch (error) {
                    throw error;
                }
            },

            async getDefaultResources() {
                this.defaultResources = [];

                try {
                    const formData = new FormData();

                    for (let x = 0; x < this.defaultChildren.length; x++) {
                        formData.append(`nestedChildren[${x}][isNestedDefault]`, 1);
                        for (const key in this.defaultChildren[x]) {
                            formData.append(`nestedChildren[${x}][${key}]`, this.defaultChildren[x][key]);
                        }
                    }

                    this.defaultResources = (
                        await Nova.request().post(
                            `/nova-vendor/nested-many/${this.resourceName}/default-resources`,
                            formData,
                            {
                                params: this.resourceRequestEditQueryString,
                            },
                        )
                    ).data.resources;
                } catch (error) {
                    throw error;
                }
            },

            addResource(resource) {
                this.resources.push(this.decorateResource(resource));
            },

            removeResource(index) {
                this.resources.splice(index, 1);
            },

            replaceResource(index, resource) {
                this.resources.splice(index, 1, this.decorateResource(resource));
            },

            getResource(index) {
                return this.resources[index];
            },

            runAction(uriKey, resource = null) {
                let resources = resource ? [resource.nestedUid] : [];

                HandlesActions.methods.runAction.call(this, uriKey, resources);
            },

            handleActionResponse(data) {
                this.nestedErrors = new Errors();
                HandlesActions.methods.handleActionResponse.call(this, data);

                if (this.selectedAction.basic) {
                    let index = -1;
                    if (this.selectedResources.length) {
                        index = this.decoratedResources.findIndex(
                            resource => resource.nestedUid === this.selectedResources[0],
                        );
                    }
                    if (index > -1) {
                        if (data.resource) {
                            this.replaceResource(index, data.resource);
                        } else {
                            this.removeResource(index);
                            this.activateResource(
                                index <= this.decoratedResources.length - 1
                                    ? index
                                    : this.decoratedResources.length - 1,
                            );
                        }
                    } else {
                        this.addResource(data.resource);
                    }
                } else {
                    this.decoratedResources = data.resources;
                }
            },
        },

        computed: {
            hasHelpText() {
                return !!this.showHelpText && !!this.field.helpText;
            },

            isLocked() {
                return this.field.lock;
            },

            minChildren() {
                return this.field.min;
            },

            maxChildren() {
                return this.field.max;
            },

            resolveComponentName() {
                return this.isPanelView ? 'form-panels-nested-resource' : 'form-tabs-nested-resource';
            },

            defaultChildren() {
                return this.field.defaultChildren;
            },

            overwriteWithDefault() {
                return this.field.defaultChildrenOverwrite;
            },

            isCreatingParent() {
                return this.field.mode === 'create';
            },

            isManagedByParent() {
                return !!this.field.managedByParent || this.isCreatingParent;
            },

            isVisible() {
                return this.field.visible;
            },

            needsReload() {
                return JSON.stringify({
                    defaultChildren: this.defaultChildren,
                    propagated: this.watchablePropagated,
                    hiddenFields: this.hiddenFields,
                });
            },

            /**
             * Build the resource request query string.
             */
            resourceRequestEditQueryString() {
                return {
                    editing: true,
                    viaResource: this.viaResource,
                    viaResourceId: this.viaResourceId,
                    viaRelationship: this.viaRelationship,
                    viaResourceRelationship: this.viaResourceRelationship,
                    relationshipType: this.relationshipType,
                };
            },
        },
    };
</script>
