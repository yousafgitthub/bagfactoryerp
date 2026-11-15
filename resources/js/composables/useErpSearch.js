import { ref } from 'vue';

const query = ref('');

export function useErpSearch() {
    return { query };
}
