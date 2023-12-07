<template>
    <BasicButton
        v-bind="{ ...$props, ...$attrs }"
        :component="component"
        ref="button"
        class="shadow relative bg-green-500 hover:bg-green-400 text-white dark:text-gray-900"
    >
        <slot />
    </BasicButton>
</template>

<script>
    import BasicButton from '@/components/Buttons/BasicButton';

    export default {
        components: { BasicButton },
        props: {
            size: {
                type: String,
                default: 'lg',
            },

            align: {
                type: String,
                default: 'center',
                validator: v => ['left', 'center'].includes(v),
            },

            component: {
                type: String,
                default: 'button',
            },
        },

        methods: {
            focus() {
                this.$refs.button.focus();
            },
        },
    };
</script>
