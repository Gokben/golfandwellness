import test from 'node:test';
import assert from 'node:assert/strict';
import { releaseChanged, canReload } from '../resources/js/releasePolicy.mjs';

test('only a different valid built asset triggers an update', () => {
    assert.equal(releaseChanged('app-old.js', 'app-new.js'), true);
    for (const [loaded, current] of [['app-old.js','app-old.js'], [undefined,'app-new.js'], ['app-old.js',''], ['app-old.js','<html>']]) assert.equal(releaseChanged(loaded,current), false);
});
test('open or minimized work windows, dialogs and hidden tabs block reload', () => {
    assert.equal(canReload(0,true,false), true);
    assert.equal(canReload(1,true,false), false);
    assert.equal(canReload(0,false,false), false);
    assert.equal(canReload(0,true,true), false);
});
