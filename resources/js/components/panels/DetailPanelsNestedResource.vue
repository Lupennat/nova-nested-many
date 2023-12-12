<template>
    <div>
        <EmptyCardNestedResource v-if="!resources.length" :singular-name="singularName" />
        <template v-else>
            <Card
                class="mb-4"
                v-for="(resource, index) in resources"
                :key="uniqueResourceKey(resource)"
                :ref="keyRef(index)"
                :class="{ 'border-2 border-primary-500': isHighlighted(index) }"
            >
                <Heading
                    :level="3"
                    class="bg-gray-200 px-6 py-2 rounded-t-lg shadow flex"
                    style="text-transform: none"
                    :class="{ 'rounded-b-lg': isCollapsed(index) }"
                >
                    {{ headingTitle(resource, index) }}
                    <div
                        class="ml-2 cursor-pointer flex items-center justify-center h-4 w-4"
                        @click="toggleCollapse(index)"
                    >
                        <Icon class="cursor-pointer" :type="isCollapsed(index) ? 'chevron-right' : 'chevron-down'" />
                    </div>
                </Heading>
                <DetailCardNestedResource
                    v-show="!isCollapsed(index)"
                    :index="index"
                    :hidden-fields="hiddenFields"
                    :resource="resource"
                    :resource-name="resourceName"
                    :via-resource="viaResource"
                    @actionExecuted="$emit('actionExecuted')"
                />
            </Card>
        </template>
    </div>
</template>
<script>
    import InteractsWithPanels from '../../mixins/InteractsWithPanels';
    import DetailCardNestedResource from '../cards/DetailCardNestedResource';
    import EmptyCardNestedResource from '../cards/EmptyCardNestedResource';

    export default {
        emits: ['actionExecuted'],

        mixins: [InteractsWithPanels],

        components: { DetailCardNestedResource, EmptyCardNestedResource },
    };
</script>
