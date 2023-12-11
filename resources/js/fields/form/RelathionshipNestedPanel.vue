<template>
    <div class="nested-many-container">
        <component
            :key="`${field.attribute}:${formUniqueId}`"
            :form-unique-id="formUniqueId"
            :is="`form-${field.component}`"
            :errors="validationErrors"
            :resource-name="resourceName"
            :resource-id="resourceId"
            :resource="resource"
            :field="field"
            :mode="mode"
            :show-help-text="showHelpText"
            @field-changed="$emit('field-changed')"
            @file-deleted="$emit('update-last-retrieved-at-timestamp')"
            @file-upload-started="$emit('file-upload-started')"
            @file-upload-finished="$emit('file-upload-finished')"
        />
    </div>
</template>

<script>
    import { BehavesAsPanel } from '@/mixins';
    import { mapProps } from '@/mixins';

    export default {
        name: 'FormRelationshipPanel',

        emits: ['field-changed', 'update-last-retrieved-at-timestamp', 'file-upload-started', 'file-upload-finished'],

        mixins: [BehavesAsPanel],

        props: {
            ...mapProps(['showHelpText', 'mode']),

            formUniqueId: {
                type: String,
            },

            validationErrors: {
                type: Object,
                required: true,
            },
        },

        computed: {
            field() {
                return this.panel.fields[0];
            },
        },
    };
</script>
