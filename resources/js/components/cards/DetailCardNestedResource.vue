<template>
    <div class="divide-gray-100 divide-y px-6">
        <component
            v-for="(field, fieldIndex) in resource.fields"
            v-show="!isHidden(field)"
            :key="`fields-${uniqueResourceKey(resource)}-${index}-${fieldIndex}`"
            :index="fieldIndex"
            :is="resolveComponentName(field)"
            :resource-name="resourceName"
            :resource-id="resourceId"
            :resource="resource"
            :field="field"
            @actionExecuted="$emit('actionExecuted')"
        />
    </div>
</template>
<script>
    import InteractsWithResource from '../../mixins/InteractsWithResource';

    export default {
        emits: ['actionExecuted'],

        mixins: [InteractsWithResource],

        props: {
            index: {
                type: Number,
                required: true,
            },

            resource: {
                type: Object,
                required: true,
            },

            hiddenFields: {
                type: Array,
                required: true,
            },
        },

        computed: {
            resourceId() {
                return this.resource.id?.value ?? null;
            },
        },

        methods: {
            /**
             * Resolve the component name.
             */
            resolveComponentName(field) {
                return field.prefixComponent ? 'detail-' + field.component : field.component;
            },

            isHidden(field) {
                return this.hiddenFields.includes(field.attribute);
            },
        },
    };
</script>
