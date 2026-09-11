import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mergeHotelDraft } from '../resources/js/mergeHotelDraft.mjs';
const base={name:'Hotel',contracts:[{id:'a',type:'',price:'100'},{id:'b',type:'',price:'200'}]};
const copy=value=>structuredClone(value);
test('refreshes untouched values while keeping other edits',()=>{
    const local=copy(base), remote=copy(base);
    local.name='Draft'; remote.contracts[0].type='Accommodation';
    const result=mergeHotelDraft(base,local,remote);
    assert.equal(result.value.name,'Draft');
    assert.equal(result.value.contracts[0].type,'Accommodation');
    assert.deepEqual(result.conflicts,[]);
});
test('same-field conflicts preserve local values and report conflict',()=>{
    const local=copy(base), remote=copy(base);
    local.contracts[0].type='Average'; remote.contracts[0].type='Accommodation';
    const result=mergeHotelDraft(base,local,remote);
    assert.equal(result.value.contracts[0].type,'Average');
    assert.deepEqual(result.conflicts,['contracts[a].type']);
});
test('merges rows by identity and preserves additions',()=>{
    const local=copy(base), remote=copy(base);
    local.contracts.reverse(); local.contracts.push({id:'c',type:'',price:'300'});
    remote.contracts[0].price='110';
    const result=mergeHotelDraft(base,local,remote);
    assert.equal(result.value.contracts.find(c=>c.id==='a').price,'110');
    assert.equal(result.value.contracts.length,3);
});
test('remote removal never erases edited row silently',()=>{
    const local=copy(base), remote=copy(base);
    local.contracts[0].price='111'; remote.contracts.shift();
    const result=mergeHotelDraft(base,local,remote);
    assert.equal(result.value.contracts[0].price,'111');
    assert.deepEqual(result.conflicts,['contracts[a]']);
});
test('identical concurrent edits are not conflicts',()=>{
    const changed=copy(base); changed.contracts[0].type='Average';
    assert.deepEqual(mergeHotelDraft(base,changed,copy(changed)).conflicts,[]);
});
