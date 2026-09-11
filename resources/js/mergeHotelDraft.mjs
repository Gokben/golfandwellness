const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);
const object = value => value !== null && typeof value === 'object' && !Array.isArray(value);
const identified = values => values.every(value => object(value) && value.id !== undefined)
    && new Set(values.map(value => String(value.id))).size === values.length;

// Three-way merge: server changes replace untouched fields, never local edits.
export function mergeHotelDraft(base, local, remote) {
    const conflicts = [];
    function merge(b, l, r, path) {
        if (same(l, b)) return r;
        if (same(r, b) || same(l, r)) return l;
        if ([b, l, r].every(Array.isArray) && [b, l, r].every(identified)) {
            const index = rows => new Map(rows.map(row => [String(row.id), row]));
            const bi = index(b), li = index(l), ri = index(r);
            return [...new Set([...l, ...r].map(row => String(row.id)))].map(id =>
                merge(bi.get(id), li.get(id), ri.get(id), `${path}[${id}]`)
            ).filter(value => value !== undefined);
        }
        if ([b, l, r].every(object)) {
            const result = {};
            for (const key of new Set([...Object.keys(b), ...Object.keys(l), ...Object.keys(r)])) {
                const value = merge(b[key], l[key], r[key], path ? `${path}.${key}` : key);
                if (value !== undefined) result[key] = value;
            }
            return result;
        }
        conflicts.push(path);
        return l;
    }
    return { value: JSON.parse(JSON.stringify(merge(base, local, remote, ''))), conflicts };
}
