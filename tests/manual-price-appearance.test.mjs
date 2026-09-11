import { test } from 'node:test';
import assert from 'node:assert/strict';
import { manualPriceAppearance } from '../resources/js/manualPriceAppearance.mjs';
test('automatic prices never have a manual border',()=>{
    assert.equal(manualPriceAppearance({manualPrice:false,manualPriceEdited:true}),false);
});
test('imported protected price is not a user override',()=>{
    assert.equal(manualPriceAppearance({manualPrice:true},true),false);
});
test('explicit manual mode has a border even on a source contract',()=>{
    assert.equal(manualPriceAppearance({manualPrice:true,manualPriceEdited:true},true),true);
});
test('existing regular manual prices retain their border',()=>{
    assert.equal(manualPriceAppearance({manualPrice:true}),true);
});
