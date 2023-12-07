import NestedFormData from './NestedFormData';
import { Errors } from 'form-backend-validation';

export default {
    mixins: [NestedFormData],

    data: () => ({
        actions: [],
        runningAction: '',
        addAction: null,
        deleteAction: null,
        restoreAction: null,
        selectedActionKey: '',
        runningActionKey: '',
        actionErrors: new Errors(),
        confirmActionModalOpened: false,
        selectedResources: [],
    }),

    methods: {
        async getActions() {
            this.actions = [];
            this.addAction = null;
            this.deleteAction = null;
            this.restoreAction = null;

            try {
                const data = (
                    await Nova.request().get('/nova-vendor/nested-many/' + this.resourceName + '/actions', {
                        params: {
                            viaResource: this.viaResource,
                            viaResourceId: this.viaResourceId,
                            viaRelationship: this.viaRelationship,
                            viaResourceRelationship: this.viaResourceRelationship,
                            relationshipType: this.relationshipType,
                            ...this.nestedPropagated,
                        },
                    })
                ).data;
                this.actions = data.actions;
                this.addAction = data.addAction;
                this.deleteAction = data.deleteAction;
                this.restoreAction = data.restoreAction;
            } catch (error) {
                throw error;
            }
        },

        runAction(event, resources = []) {
            this.selectedResources = resources;
            this.selectedActionKey = event;

            this.determineActionStrategy();
        },

        /**
         * Determine whether the action should redirect or open a confirmation modal
         */
        determineActionStrategy() {
            if (this.selectedAction.withoutConfirmation) {
                this.executeAction();
            } else {
                this.openConfirmationModal();
            }
        },

        /**
         * Confirm with the user that they actually want to run the selected action.
         */
        openConfirmationModal() {
            this.confirmActionModalOpened = true;
        },

        /**
         * Close the action confirmation modal.
         */
        closeConfirmationModal() {
            this.confirmActionModalOpened = false;
            this.actionErrors = new Errors();
        },

        /**
         * Execute the selected action.
         */
        executeAction() {
            this.runningActionKey = this.selectedActionKey;
            this.actionErrors = new Errors();
            Nova.$progress.start();

            Nova.request({
                method: 'post',
                url: `/nova-vendor/nested-many/${this.resourceName}/action`,
                params: this.actionRequestQueryString,
                data: this.actionFormData(),
            })
                .then(response => {
                    if (!this.keepOpened) {
                        this.confirmActionModalOpened = false;
                    } else if (this.selectedResources.length === 1) {
                        const found = this.selectedAction.basic
                            ? response.data.resource?.nestedUid == this.selectedResources[0]
                            : response.data.resources.find(resource => resource.nestedUid == this.selectedResources[0]);
                        if (!found) {
                            this.confirmActionModalOpened = false;
                        }
                    }

                    this.handleActionResponse(response.data);

                    this.runningActionKey = '';
                    Nova.$progress.done();
                    if (this.$refs.selectControl) {
                        this.$refs.selectControl.selectedIndex = 0;
                    }
                })
                .catch(error => {
                    this.runningActionKey = '';
                    Nova.$progress.done();

                    if (error.response && error.response.status == 422) {
                        this.actionErrors = new Errors(error.response.data.errors);

                        Nova.error(this.__('There was a problem executing the action.'));
                    }
                });
        },

        /**
         * Gather the action FormData for the given action.
         */
        actionFormData() {
            return _.tap(
                this.generateResourcesFormData(
                    new FormData(),
                    'nestedChildren',
                    this.resources,
                    this.primaryKeyName,
                    true,
                ),
                formData => {
                    if (this.selectedResources.length) {
                        formData.append('nestedResources[]', this.selectedResources);
                    }

                    _.each(this.selectedAction.fields, field => {
                        field.fill(formData);
                    });
                },
            );
        },

        emitResponseCallback(callback) {
            this.$emit('actionExecuted');
            Nova.$emit('action-executed');

            if (typeof callback === 'function') {
                callback();
            }
        },

        /**
         * Handle the action response. Typically either a message, download or a redirect.
         */
        handleActionResponse() {
            if (!this.isSelectedActionBasic) {
                Nova.success('Succesfully executed!');
            }
        },
    },

    computed: {
        keepOpened() {
            return this.selectedAction?.keepOpened ?? false;
        },

        allActions() {
            return [this.addAction, this.deleteAction, this.restoreAction, ...this.actions].filter(Boolean);
        },
        /**
         * Return the selected action being executed.
         */
        selectedAction() {
            if (this.selectedActionKey) {
                return _.find(this.allActions, a => a.uriKey == this.selectedActionKey);
            }
        },
        /**
         * Get the query string for an action request.
         */
        actionRequestQueryString() {
            return {
                action: this.selectedActionKey,
                pivotAction: false,
                viaResource: this.viaResource,
                viaResourceId: this.viaResourceId,
                viaRelationship: this.viaRelationship,
            };
        },
        /**
         * Get all of the available actions for the resource.
         */
        availableActions() {
            return _.filter(this.actions, action => {
                return !action.standalone;
            });
        },

        /**
         * Get all of the available actions for the resource.
         */
        availableStandaloneActions() {
            return _.filter(this.actions, action => {
                return action.standalone;
            });
        },

        syncEndpoint() {
            return `/nova-vendor/nested-many/${this.resourceName}/action`;
        },

        isSelectedActionBasic() {
            return [this.addAction.uriKey, this.deleteAction.uriKey, this.restoreAction.uriKey].includes(
                this.selectedActionKey,
            );
        },
    },
};
