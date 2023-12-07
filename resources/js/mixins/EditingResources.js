import InteractsWithResources from './InteractsWithResources';

import { mapProps } from '@/mixins';
import { Errors } from 'form-backend-validation';

export default {
    emits: ['run-action', 'field-changed'],

    mixins: [InteractsWithResources],

    props: {
        ...mapProps(['showHelpText', 'viaResourceId', 'viaRelationship']),

        authorizedToCreateNested: {
            type: Boolean,
            required: true,
        },

        errors: {
            type: Object,
            required: true,
        },

        hasSoftDelete: {
            type: Boolean,
            required: true,
        },

        isLocked: {
            type: Boolean,
            required: true,
        },

        minChildren: {
            required: true,
        },

        maxChildren: {
            required: true,
        },

        validationKey: {
            type: String,
            required: true,
        },

        availableActions: {
            type: Array,
            required: true,
        },

        primaryKeyName: {
            type: String,
            required: true,
        },

        addAction: {
            required: true,
        },

        deleteAction: {
            required: true,
        },

        restoreAction: {
            required: true,
        },

        runningAction: {
            type: String,
            required: true,
        },
    },

    methods: {
        hasErrors(index) {
            if (this.resources[index].isNestedSoftDeleted) {
                return false;
            }

            index = this.adjustIndexForValidation(index);

            return this.errors.has(`${this.validationKey}.${index}`);
        },

        adjustIndexForValidation(index) {
            return index - this.resources.slice(0, index).filter(resource => resource.isNestedSoftDeleted).length;
        },

        getResourceErrors(index) {
            if (this.resources[index].isNestedSoftDeleted) {
                return new Errors({});
            }

            index = this.adjustIndexForValidation(index);

            return new Errors(
                Object.keys(this.errors.all())
                    .filter(e => e.startsWith(`${this.validationKey}.${index}.`))
                    .reduce((carry, key) => {
                        carry[key.replace(`${this.validationKey}.${index}.`, '')] = this.errors.get(key);
                        return carry;
                    }, {}),
            );
        },
    },

    computed: {
        notDeletedResources() {
            return this.resources.filter(resource => !resource.isNestedSoftDeleted);
        },

        canDelete() {
            return !this.isLocked && (this.minChildren === null || this.notDeletedResources.length > this.minChildren);
        },

        canRestore() {
            return this.maxChildren === null || this.notDeletedResources.length < this.maxChildren;
        },

        autorizedToCreate() {
            return (
                this.authorizedToCreateNested &&
                !this.isLocked &&
                (this.maxChildren === null || this.notDeletedResources.length < this.maxChildren)
            );
        },
    },
};
