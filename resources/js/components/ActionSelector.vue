<template>
    <SelectControl
        v-bind="$attrs"
        size="xs"
        @change="runAction"
        :options="actionsForSelect"
        :disabled="working"
        selected=""
        :class="{ 'max-w-[6rem]': width == 'auto', 'w-full': width == 'full' }"
        :aria-label="__('Select Action')"
        ref="selectControl"
    >
        <option value="" disabled selected>{{ __('Actions') }}</option>
    </SelectControl>
</template>

<script>
    export default {
        inheritAttrs: false,
        props: {
            width: {
                type: String,
                default: 'auto',
            },
            working: {
                type: Boolean,
                required: true,
            },
            actions: { type: Array },
        },

        methods: {
            runAction(uriKey) {
                this.$refs.selectControl.resetSelection();
                this.$emit('run-action', uriKey);
            },
        },

        computed: {
            actionsForSelect() {
                return this.actions.map(a => ({ value: a.uriKey, label: a.name }));
            },
        },
    };
</script>
