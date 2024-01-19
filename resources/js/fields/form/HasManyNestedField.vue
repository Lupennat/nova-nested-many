<template>
    <ResourceFormNested
        :field="currentField"
        :help-text="helpText"
        ref="nestedForm"
        :show-help-text="showHelpText"
        :mode="mode"
        :errors="errors"
        :resource-name="field.resourceName"
        :via-resource="resourceName"
        :via-resource-id="resourceId"
        :via-relationship="field.hasManyRelationship"
        :relationship-type="'hasMany'"
        @field-changed="$emit('field-changed')"
        @file-deleted="$emit('file-deleted')"
        @file-upload-started="$emit('file-upload-started')"
        @file-upload-finished="$emit('file-upload-finished')"
    />
</template>

<script>
    import { mapProps } from '@/mixins';

    import { DependentFormField } from 'laravel-nova';

    export default {
        emits: ['field-changed', 'file-deleted', 'file-upload-started', 'file-upload-finished'],

        mixins: [DependentFormField],

        props: {
            ...mapProps([
                'showHelpText',
                'mode',
                'resourceName',
                'resourceId',
                'viaResource',
                'viaResourceId',
                'viaRelationship',
            ]),

            helpText: {
                default: null,
            },

            field: {
                type: Object,
            },

            formUniqueId: {
                type: String,
            },

            errors: {
                type: Object,
                required: true,
            },
        },
        methods: {
            fill(formData, withDelete = false, nestedValidationKeyPrefix = '') {
                this.$refs.nestedForm.fill(formData, withDelete, nestedValidationKeyPrefix);
            },
        },
    };
</script>
