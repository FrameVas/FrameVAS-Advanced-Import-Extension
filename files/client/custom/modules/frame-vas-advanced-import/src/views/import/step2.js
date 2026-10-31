/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

define('frame-vas-advanced-import:views/import/step2', ['views/import/step2'], function (Dep) {

    /**
     * @typedef {Object} AdvancedImportForeignItem
     * @property {string} field A foreign field of the imported entity type.
     * @property {string} link A belongs-to link the foreign field goes through.
     * @property {string} foreignEntityType A related entity type.
     * @property {string} foreignField A field of the related entity type.
     * @property {string} label A label shown in the mapping dropdown.
     */

    /**
     * The core field-mapping step. Foreign fields are offered as `Link (Field)` options,
     * placed right after the link's `(ID)` and `(Name)` options.
     */
    return class extends Dep {

        /**
         * @private
         * @type {Map<string, AdvancedImportForeignItem>|null}
         */
        advancedImportForeignMap = null

        /**
         * Field types of the related entity that can be matched. Mirrors the backend.
         *
         * @private
         * @type {string[]}
         */
        advancedImportDefaultTypeList = [
            'varchar',
            'int',
            'float',
            'number',
            'enum',
            'enumInt',
            'enumFloat',
            'url',
            'email',
            'phone',
        ]

        /**
         * @return {Map<string, AdvancedImportForeignItem>}
         */
        getAdvancedImportForeignMap() {
            if (this.advancedImportForeignMap) {
                return this.advancedImportForeignMap;
            }

            const map = new Map();
            const scope = this.scope;
            const metadata = this.getMetadata();
            const acl = this.getAcl();

            const fieldDefsMap = metadata.get(['entityDefs', scope, 'fields']) || {};
            const linkDefsMap = metadata.get(['entityDefs', scope, 'links']) || {};

            let typeList = metadata.get(['app', 'frameVasAdvancedImport', 'foreignFieldTypeList']);

            if (!Array.isArray(typeList) || !typeList.length) {
                typeList = this.advancedImportDefaultTypeList;
            }

            const forbiddenEditList = acl.getScopeForbiddenFieldList(scope, 'edit') || [];
            const forbiddenReadList = acl.getScopeForbiddenFieldList(scope, 'read') || [];

            for (const field in fieldDefsMap) {
                const defs = fieldDefsMap[field] || {};

                if (defs.type !== 'foreign' || defs.disabled || defs.importDisabled) {
                    continue;
                }

                const link = defs.link;
                const foreignField = defs.field;

                if (typeof link !== 'string' || !link || typeof foreignField !== 'string' || !foreignField) {
                    continue;
                }

                const linkFieldDefs = fieldDefsMap[link];
                const linkDefs = linkDefsMap[link];

                if (
                    !linkFieldDefs ||
                    linkFieldDefs.disabled ||
                    linkFieldDefs.importDisabled ||
                    linkFieldDefs.readOnly ||
                    !linkDefs ||
                    linkDefs.type !== 'belongsTo' ||
                    !linkDefs.entity
                ) {
                    continue;
                }

                if (forbiddenEditList.includes(link) || forbiddenReadList.includes(field)) {
                    continue;
                }

                const foreignEntityType = linkDefs.entity;

                if (!acl.checkScope(foreignEntityType, 'read')) {
                    continue;
                }

                const foreignFieldType = metadata.get(['entityDefs', foreignEntityType, 'fields', foreignField, 'type']);

                if (!typeList.includes(foreignFieldType)) {
                    continue;
                }

                const label = this.translate(link, 'fields', scope) + ' (' +
                    this.translate(foreignField, 'fields', foreignEntityType) + ')';

                map.set(field, {
                    field: field,
                    link: link,
                    foreignEntityType: foreignEntityType,
                    foreignField: foreignField,
                    label: label,
                });
            }

            this.advancedImportForeignMap = map;

            return map;
        }

        /**
         * @return {string[]}
         */
        getAttributeList() {
            const map = this.getAdvancedImportForeignMap();

            // Foreign fields with the core "Relate on Import" option are already listed; re-position them.
            const list = super.getAttributeList().filter(attribute => !map.has(attribute));

            const fieldList = Array.from(map.keys())
                .sort((v1, v2) => map.get(v1).label.localeCompare(map.get(v2).label));

            fieldList.forEach(field => {
                const link = map.get(field).link;

                let index = -1;

                list.forEach((attribute, i) => {
                    if (
                        attribute === link + 'Id' ||
                        attribute === link + 'Name' ||
                        (map.has(attribute) && map.get(attribute).link === link)
                    ) {
                        index = i;
                    }
                });

                if (index === -1) {
                    list.push(field);

                    return;
                }

                list.splice(index + 1, 0, field);
            });

            return list;
        }

        /**
         * @param {number} num
         * @param {string|null} name
         * @return {JQuery}
         */
        getFieldDropdown(num, name) {
            const $select = super.getFieldDropdown(num, name);
            const map = this.getAdvancedImportForeignMap();

            if (!map.size) {
                return $select;
            }

            /** @type {HTMLSelectElement} */
            const select = $select.get(0);

            Array.from(select.options).forEach(option => {
                const item = map.get(option.value);

                if (item) {
                    option.textContent = item.label;
                }
            });

            // A header such as "Підрозділ (Код Підрозділу в 1С)" selects the matching option.
            if (name && select.value === '') {
                const header = name.trim().toLowerCase();

                for (const item of map.values()) {
                    if (item.label.toLowerCase() === header) {
                        select.value = item.field;

                        break;
                    }
                }
            }

            return $select;
        }
    };
});
