<template>
    <ResourceDetailNested
        :field="field"
        :resource-name="field.resourceName"
        :via-resource="resourceName"
        :via-resource-id="resourceId"
        :via-relationship="field.hasManyRelationship"
        :relationship-type="'hasMany'"
        @actionExecuted="actionExecuted"
    />
</template>

<script>
    export default {
        emits: ['actionExecuted'],

        props: ['resourceName', 'resourceId', 'resource', 'field'],

        methods: {
            /**
             * Handle the actionExecuted event and pass it up the chain.
             */
            actionExecuted() {
                this.$emit('actionExecuted');
            },
        },
    };
</script>
