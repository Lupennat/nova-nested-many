class NestedFormData {
    constructor(attribute, formData) {
        this.attribute = attribute;
        this.parentFormData = formData;
        this.localFormData = new FormData();
    }

    append(name, ...args) {
        this.localFormData.append(name, ...args);
        this.parentFormData.append(this.name(name), ...args);
    }

    delete(name) {
        this.localFormData.delete(name);
        this.parentFormData.delete(this.name(name));
    }

    entries() {
        return this.localFormData.entries();
    }

    get(name) {
        return this.localFormData.get(name);
    }

    getAll(name) {
        return this.localFormData.getAll(name);
    }

    has(name) {
        return this.localFormData.has(name);
    }

    keys() {
        return this.localFormData.keys();
    }

    set(name, ...args) {
        this.localFormData.set(name, ...args);
        this.parentFormData.set(this.name(name), ...args);
    }

    values() {
        return this.localFormData.values();
    }

    name(attribute) {
        let [name, ...nested] = attribute.split('[');

        if (!_.isNil(nested) && nested.length > 0) {
            return `${this.attribute}[${name}][${nested.join('[')}`;
        }

        return `${this.attribute}[${attribute}]`;
    }
}

export default {
    methods: {
        generateResourcesFormData(formData, attribute, resources, primaryKeyName, withDeleted = false) {
            for (const key in this.nestedPropagated) {
                formData.append(key, this.nestedPropagated[key]);
            }
            let formIndex = 0;
            _.each(resources, (resource, index) => {
                if (!resource.loading) {
                    _.tap(new NestedFormData(`${attribute}[${formIndex}]`, formData), resourceForm => {
                        if (!withDeleted && resource.isNestedSoftDeleted) {
                            return;
                        }

                        resourceForm.append(primaryKeyName, resource.primaryKey ?? '');
                        resourceForm.append('isNestedDefault', resource.isNestedDefault ? 1 : 0);
                        resourceForm.append('isNestedActive', resource.isNestedActive ? 1 : 0);
                        resourceForm.append('isNestedSoftDeleted', resource.isNestedSoftDeleted ? 1 : 0);
                        resourceForm.append('nestedUid', resource.nestedUid);

                        _.each(resource.fields, field => {
                            field.fill(resourceForm);
                        });

                        formIndex++;
                    });
                }
            });

            return formData;
        },
    },
};
