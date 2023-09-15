import InlineFormData from '@/fields/Form/InlineFormData';

export default {
    methods: {
        generateResourcesFormData(formData, attribute, resources, primaryKeyName, withDeleted = false) {
            let formIndex = 0;
            _.each(resources, (resource, index) => {
                if (!resource.loading) {
                    _.tap(new InlineFormData(`${attribute}[${formIndex}]`, formData), resourceForm => {
                        if (!withDeleted && resource.isNestedSoftDeleted) {
                            return;
                        }

                        resourceForm.append(primaryKeyName, resource.primaryKey ?? '');
                        resourceForm.append('isNestedDefault', resource.isNestedDefault ? 1 : 0);
                        resourceForm.append('isNestedActive', resource.isNestedActive ? 1 : 0);
                        resourceForm.append('isNestedSoftDeleted', resource.isNestedSoftDeleted ? 1 : 0);
                        resourceForm.append('nestedUid', resource.uid);

                        _.each(resource.fields, field => {
                            field.fill(resourceForm);
                        });

                        formIndex++;
                    });
                }
            });

            return formData;
        }
    }
};
