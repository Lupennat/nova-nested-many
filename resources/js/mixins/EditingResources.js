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
            required: true
        },

        errors: {
            type: Object,
            required: true
        },

        hasSoftDelete: {
            type: Boolean,
            required: true
        },

        isLocked: {
            type: Boolean,
            required: true
        },

        validationKey: {
            type: String,
            required: true
        },

        availableActions: {
            type: Array,
            required: true
        },

        primaryKeyName: {
            type: String,
            required: true
        },

        addAction: {
            required: true
        },

        deleteAction: {
            required: true
        },

        restoreAction: {
            required: true
        },

        runningAction: {
            type: String,
            required: true
        }
    },

    methods: {
        hasErrors(index) {
            return this.errors.has(`${this.validationKey}.${index}`);
        },

        getResourceErrors(index) {
            return new Errors(
                Object.keys(this.errors.all())
                    .filter(e => e.startsWith(`${this.validationKey}.${index}.`))
                    .reduce((carry, key) => {
                        carry[key.replace(`${this.validationKey}.${index}.`, '')] = this.errors.get(key);
                        return carry;
                    }, {})
            );
        }
    },

    computed: {
        autorizedToCreate() {
            return this.authorizedToCreateNested && !this.isLocked;
        }
    }
};
