<template>
    <div v-if="shouldShowDropdown">
        <Dropdown>
            <span class="sr-only">{{ __('Resource Row Dropdown') }}</span>
            <DropdownTrigger :show-arrow="false">
                <span class="py-0.5 px-2 rounded">
                    <Icon :solid="true" type="dots-horizontal" />
                </span>
            </DropdownTrigger>

            <template #menu>
                <DropdownMenu width="auto" class="px-1">
                    <ScrollWrap :height="250" class="divide-y divide-gray-100 dark:divide-gray-800 divide-solid">
                        <div v-if="actions.length > 0" class="py-1">
                            <!-- User Actions -->
                            <DropdownMenuItem
                                as="button"
                                v-for="action in actions"
                                :key="action.uriKey"
                                @click="$emit('run-action', action.uriKey)"
                                :title="action.name"
                                :destructive="action.destructive"
                                :disabled="!!runningAction"
                            >
                                {{ action.name }}
                            </DropdownMenuItem>
                        </div>
                    </ScrollWrap>
                </DropdownMenu>
            </template>
        </Dropdown>
    </div>
</template>

<script>
    import Dropdown from '@/components/Dropdowns/Dropdown';
    import DropdownMenu from '@/components/Dropdowns/DropdownMenu';
    import DropdownTrigger from '@/components/Dropdowns/DropdownTrigger';
    import DropdownMenuItem from '@/components/Dropdowns/DropdownMenuItem';
    import ScrollWrap from '@/components/ScrollWrap';

    export default {
        components: {
            Dropdown,
            DropdownMenu,
            DropdownTrigger,
            DropdownMenuItem,
            ScrollWrap,
        },
        props: {
            runningAction: {
                type: String,
                required: true,
            },
            actions: { type: Array },
        },

        data: () => ({}),

        computed: {
            currentTrashed() {
                return '';
            },

            shouldShowDropdown() {
                return this.actions.length > 0;
            },
        },
    };
</script>
