<template>
    <div class="divide-y" :class="[isSoftDeleted ? 'bg-yellow-100 divide-gray-800' : 'divide-gray-100']">
        <component
            v-for="(field, fieldIndex) in resource.fields"
            v-show="!isHidden(field)"
            mode="form"
            :is="resolveComponentName(field)"
            :key="`fields-${this.isEditable ? 'edit-' : ''}${uniqueResourceKey(resource)}-${index}-${fieldIndex}`"
            :index="fieldIndex"
            :errors="errors"
            :resource-name="resourceName"
            :resource-id="resourceId"
            :field="field"
            :resource="resource"
            :via-resource="viaResource"
            :via-resource-id="viaResourceId"
            :via-relationship="viaRelationship"
            :form-unique-id="formUniqueId"
            :show-help-text="showHelpText"
            @field-changed="$emit('field-changed')"
            @file-deleted="$emit('file-deleted')"
            @file-upload-started="$emit('file-upload-started')"
            @file-upload-finished="$emit('file-upload-finished')"
        />
        <div class="relative flex justify-end space-x-2 p-2" v-show="hasActionButtons">
            <InlineNestedActionDropdown
                :actions="availableActions"
                :running-action="runningAction"
                @run-action="$emit('run-action', $event)"
                v-show="!isSoftDeleted"
            />
            <LoadingButton
                type="button"
                :disabled="!!runningAction"
                component="DangerButton"
                @click="$emit('run-action', deleteAction.uriKey, resource)"
                v-if="autorizedToDelete && !isSoftDeleted"
            >
                <Icon type="trash" class="mr-2" /> {{ __(`Remove ${singularName}`) }}
            </LoadingButton>
            <LoadingButton
                type="button"
                :disabled="!!runningAction"
                component="NestedManySuccessButton"
                @click="$emit('run-action', restoreAction.uriKey)"
                v-if="isSoftDeleted"
            >
                <Icon type="reply" class="mr-2" /> {{ __(`Restore ${singularName}`) }}
            </LoadingButton>
        </div>
    </div>
</template>
<script>
    import { uid } from 'uid/single';
    import { mapProps } from '@/mixins';

    import InteractsWithResource from '../../mixins/InteractsWithResource';

    export default {
        emits: ['run-action', 'field-changed', 'file-deleted', 'file-upload-started', 'file-upload-finished'],

        mixins: [InteractsWithResource],

        props: {
            ...mapProps(['showHelpText', 'viaResourceId', 'viaRelationship']),

            index: {
                type: Number,
                required: true
            },

            resource: {
                type: Object,
                required: true
            },

            hiddenFields: {
                type: Array,
                required: true
            },

            errors: {
                type: Object,
                required: true
            },

            canDelete: {
                type: Boolean,
                required: true
            },

            singularName: {
                type: String,
                required: true
            },

            hasSoftDelete: {
                type: Boolean,
                required: true
            },

            availableActions: {
                type: Array,
                required: true
            },

            runningAction: {
                type: String,
                required: true
            },

            deleteAction: {
                required: true
            },

            restoreAction: {
                required: true
            }
        },

        data: () => ({
            formUniqueId: uid()
        }),

        mounted() {
            Nova.$emit('resource-loaded', {
                resourceName: this.resourceName,
                resourceId: this.resourceId ? this.resourceId.toString() : null,
                mode: this.editMode
            });
        },

        methods: {
            /**
             * Resolve the component name.
             */
            resolveComponentName(field) {
                return field.prefixComponent ? 'form-' + field.component : field.component;
            },

            isHidden(field) {
                return this.hiddenFields.includes(field.attribute);
            }
        },

        computed: {
            isSoftDeleted() {
                return this.resource.isNestedSoftDeleted;
            },

            hasActionButtons() {
                return this.autorizedToDelete || this.availableActions.length > 0;
            },

            isEditable() {
                return this.autorizedToUpdate && !this.isSoftDeleted;
            },

            autorizedToUpdate() {
                return this.resource.authorizedToUpdateNested;
            },

            autorizedToDelete() {
                return (
                    this.resource.authorizedToDeleteNested && this.deleteAction && this.restoreAction && this.canDelete
                );
            },

            resourceId() {
                return this.resource.id?.value ?? null;
            },

            editMode() {
                return this.resourceId === null ? 'create' : 'update';
            }
        }
    };
</script>
