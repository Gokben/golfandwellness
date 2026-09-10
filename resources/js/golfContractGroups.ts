import type { GolfContract } from './golfContracts.ts';
import { sourceContractGroups } from './golfContractSourceGroups.ts';

const sourceParents = new Map(Object.entries(sourceContractGroups).flatMap(([parent, ids]) => ids.map(id => [id, parent] as const)));
export const contractGroupId = (row: GolfContract): string => row.groupId ?? sourceParents.get(row.id) ?? row.id;
export function groupGolfContracts(rows: GolfContract[]) {
    const groups = new Map<string, { id: string; primary: GolfContract; rows: GolfContract[] }>();
    for (const row of rows) {
        const id = contractGroupId(row);
        const key = JSON.stringify([row.courseKey, id]);
        const group = groups.get(key);
        if (group) { group.rows.push(row); if (row.id === id) group.primary = row; }
        else groups.set(key, { id, primary: row, rows: [row] });
    }
    return [...groups.values()];
}
export function copyContractGroup(rows: GolfContract[], primary: GolfContract, nextId: () => string): GolfContract[] {
    const groupId = nextId();
    const game = `${primary.game.slice(0, 140)} (Kopya)`;
    return rows.map(row => ({ ...row, id: row.id === primary.id ? groupId : nextId(), groupId, game }));
}
