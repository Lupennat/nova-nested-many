<script>
    import Dropdown from '@/components/Dropdowns/Dropdown';

    export default {
        extends: Dropdown,
        computed: {
            extraClasses() {
                return ['z-50'];
            },
        },
    };
</script>
