<template>
    <div v-if="shouldShowDropdown">
        <Dropdown class="h-9">
            <span class="sr-only">{{ __('Resource Row Dropdown') }}</span>
            <slot name="trigger">
                <DropdownTrigger :dusk="triggerDuskAttribute" :show-arrow="false">
                    <BasicButton component="span">
                        <Icon :solid="true" type="dots-horizontal" />
                    </BasicButton>
                </DropdownTrigger>
            </slot>

            <template #menu>
                <DropdownMenu width="auto" class="px-1">
                    <ScrollWrap :height="250" class="divide-y divide-gray-100 dark:divide-gray-800 divide-solid">
                        <div v-if="actions.length > 0" class="py-1">
                            <!-- User Actions -->
                            <DropdownMenuItem
                                class="border-none"
                                as="button"
                                v-for="action in actions"
                                :data-action-id="action.uriKey"
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
    import BasicButton from '@/components/Buttons/BasicButton';
    import Dropdown from './Dropdown';

    export default {
        components: { BasicButton, Dropdown },
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
