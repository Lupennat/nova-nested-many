<template>
    <div>
        <EmptyCardNestedResource v-if="!resources.length" :singular-name="singularName" />
        <template v-else>
            <div v-for="(resource, index) in resources" :key="uniqueResourceKey(resource)" :ref="keyRef(index)">
                <Card
                    class="mb-4"
                    :class="{
                        'border-2 border-red-500': hasErrors(index),
                        'border-2 border-primary-500': !hasErrors(index) && isHighlighted(index),
                    }"
                >
                    <Heading
                        :level="3"
                        class="bg-gray-200 px-6 py-2 rounded-t-lg shadow flex"
                        style="text-transform: none"
                        :class="{ 'rounded-b-lg': isCollapsed(index) }"
                    >
                        <div class="flex" :class="{ 'line-through': resource.isNestedSoftDeleted }">
                            {{ headingTitle(resource, index) }}
                            <div
                                class="ml-2 cursor-pointer flex items-center justify-center h-4 w-4"
                                @click="toggleCollapse(index)"
                            >
                                <Icon
                                    class="cursor-pointer"
                                    :type="isCollapsed(index) ? 'chevron-right' : 'chevron-down'"
                                />
                            </div>
                        </div>
                    </Heading>
                    <FormCardNestedResource
                        v-show="!isCollapsed(index)"
                        :index="index"
                        :errors="getResourceErrors(index)"
                        :hidden-fields="hiddenFields"
                        :resource="resource"
                        :resource-name="resourceName"
                        :via-resource="viaResource"
                        :via-resource-id="viaResourceId"
                        :via-relationship="viaRelationship"
                        :show-help-text="showHelpText"
                        :can-delete="canDelete"
                        :can-restore="canRestore"
                        :singular-name="singularName"
                        :has-soft-delete="hasSoftDelete"
                        :available-actions="availableActions"
                        :running-action="runningAction"
                        :delete-action="deleteAction"
                        :restore-action="restoreAction"
                        @run-action="$emit('run-action', $event, resource)"
                        @field-changed="$emit('field-changed')"
                        @file-deleted="$emit('file-deleted')"
                        @file-upload-started="$emit('file-upload-started')"
                        @file-upload-finished="$emit('file-upload-finished')"
                    />
                </Card>
            </div>
        </template>
        <div class="flex py-4" v-if="autorizedToCreate && addAction">
            <CreateActionButton
                class="ml-auto flex items-center"
                :actions="[addAction]"
                :running-action="runningAction"
                @run-action="$emit('run-action', $event)"
            />
        </div>
    </div>
</template>
<script>
    import EditingResources from '../../mixins/EditingResources';
    import InteractsWithPanels from '../../mixins/InteractsWithPanels';

    import FormCardNestedResource from '../cards/FormCardNestedResource';
    import EmptyCardNestedResource from '../cards/EmptyCardNestedResource';
    import CreateActionButton from '../CreateActionButton';

    export default {
        emits: ['file-deleted', 'file-upload-started', 'file-upload-finished'],

        mixins: [EditingResources, InteractsWithPanels],

        components: { FormCardNestedResource, EmptyCardNestedResource, CreateActionButton },

        watch: {
            lastActiveIndex(val, oldVal) {
                if (val !== oldVal) {
                    this.$nextTick(() => {
                        this.scrollResourceIntoView(val);
                    });
                    this.highlighted = val;
                }
            },
        },
    };
</script>
