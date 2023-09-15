<template>
    <Card class="mb-4">
        <div class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 md:flex items-center">
            <ul class="flex flex-wrap -mb-px">
                <li
                    class="mr-2"
                    v-for="(resource, index) in resources"
                    :key="`tab-${this.resourceName}-${this.viaResource}-${index}`"
                >
                    <span
                        v-if="lastActiveIndex === index"
                        :class="{
                            'line-through border-gray-500 text-gray-500': resource.isNestedSoftDeleted,
                            'border-red-500 text-red-500': hasErrors(index),
                            'border-primary-500 text-primary-500': !resource.isNestedSoftDeleted && !hasErrors(index)
                        }"
                        class="cursor-default inline-block p-4 border-b-2 font-bold"
                    >
                        {{ headingTitle(resource, index) }}
                    </span>
                    <a
                        v-else
                        @click="activate(index)"
                        :class="{
                            'line-through hover:text-gray-500': resource.isNestedSoftDeleted,
                            'border-red-500 border-b-2 hover:text-red-500': hasErrors(index),
                            'border-primary-300 text-primary-300 font-bold': resource.isNestedActive,
                            'border-transparent': !resource.isNestedActive,
                            'hover:text-primary-500': !resource.isNestedSoftDeleted && !hasErrors(index)
                        }"
                        class="cursor-pointer inline-block p-4 border-b-2"
                    >
                        {{ headingTitle(resource, index) }}
                    </a>
                </li>
            </ul>
            <CreateActionButton
                v-if="autorizedToCreate"
                class="ml-auto md:flex items-center mr-2 mb-2 mt-2"
                :actions="[addAction]"
                :running-action="runningAction"
                @run-action="$emit('run-action', $event)"
            />
        </div>

        <div
            class="mb-4"
            v-for="(resource, index) in resources"
            v-show="lastActiveIndex === index"
            :key="uniqueResourceKey(resource)"
        >
            <FormCardNestedResource
                :index="index"
                :errors="getResourceErrors(index)"
                :hidden-fields="hiddenFields"
                :resource="resource"
                :resource-name="resourceName"
                :via-resource="viaResource"
                :via-resource-id="viaResourceId"
                :via-relationship="viaRelationship"
                :show-help-text="showHelpText"
                :is-locked="isLocked"
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
        </div>
    </Card>
</template>
<script>
    import EditingResources from '../../mixins/EditingResources';
    import FormCardNestedResource from '../cards/FormCardNestedResource';
    import CreateActionButton from '../CreateActionButton';

    export default {
        emits: ['file-deleted', 'file-upload-started', 'file-upload-finished'],

        mixins: [EditingResources],

        components: { FormCardNestedResource, CreateActionButton }
    };
</script>
