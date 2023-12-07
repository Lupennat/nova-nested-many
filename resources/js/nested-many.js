import FormNRelathionshipNestedPanel from './fields/form/RelathionshipNestedPanel';
import DetailRelathionshipNestedPanel from './fields/detail/RelathionshipNestedPanel';

import DetailHasManyNestedField from './fields/detail/HasManyNestedField';
import FormHasManyNestedField from './fields/form/HasManyNestedField';

import DetailNested from './views/DetailNested';
import FormNested from './views/FormNested';

import InlineNestedActionDropdown from './components/dropdown/InlineNestedActionDropdown';
import NestedManySuccessButton from './components/buttons/SuccessButton';
import NestedManyDangerButton from './components/buttons/DangerButton';

Nova.booting((app, store) => {
    app.component('InlineNestedActionDropdown', InlineNestedActionDropdown);
    app.component('NestedManySuccessButton', NestedManySuccessButton);
    app.component('NestedManyDangerButton', NestedManyDangerButton);
    app.component('ResourceDetailNested', DetailNested);
    app.component('ResourceFormNested', FormNested);
    app.component('detail-relationship-nested-panel', DetailRelathionshipNestedPanel);
    app.component('form-relationship-nested-panel', FormNRelathionshipNestedPanel);
    app.component('detail-has-many-nested-field', DetailHasManyNestedField);
    app.component('form-has-many-nested-field', FormHasManyNestedField);
});
