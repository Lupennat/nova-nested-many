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
                        class="cursor-default inline-block p-4 border-b-2 font-bold border-primary-500 text-primary-500"
                    >
                        {{ headingTitle(resource, index) }}
                    </span>
                    <a
                        v-else
                        @click="activate(index)"
                        class="cursor-pointer inline-block p-4 border-b-2 border-transparent hover:text-primary-500"
                        :class="{ 'border-primary-500': resource.isNestedActive }"
                    >
                        {{ headingTitle(resource, index) }}
                    </a>
                </li>
            </ul>
        </div>
        <EmptyCardNestedResource v-if="!resources.length" :singular-name="singularName" />
        <template v-else>
            <div
                class="mb-4"
                v-for="(resource, index) in resources"
                v-show="lastActiveIndex === index"
                :key="uniqueResourceKey(resource)"
            >
                <DetailCardNestedResource
                    :index="index"
                    :hidden-fields="hiddenFields"
                    :resource="resource"
                    :resource-name="resourceName"
                    :via-resource="viaResource"
                    @actionExecuted="$emit('actionExecuted')"
                />
            </div>
        </template>
    </Card>
</template>
<script>
    import InteractsWithResources from '../../mixins/InteractsWithResources';

    import DetailCardNestedResource from '../cards/DetailCardNestedResource';
    import EmptyCardNestedResource from '../cards/EmptyCardNestedResource';

    export default {
        emits: ['actionExecuted'],

        mixins: [InteractsWithResources],

        components: { DetailCardNestedResource, EmptyCardNestedResource },

        props: {
            collapsedChildren: {
                default: false,
            },
        },
    };
</script>
