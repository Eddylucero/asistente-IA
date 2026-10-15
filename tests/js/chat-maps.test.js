import test from 'node:test';
import assert from 'node:assert/strict';
import { utcMapCard, initChatMaps } from '../../resources/js/ui/chat-maps.js';

test('map answers expose the location and external link without loading Google on entry', () => {
    const card = utcMapCard('PUJILI');
    assert.match(card, /Extensión Pujilí/);
    assert.match(card, /Abrir en Google Maps/);
    assert.match(card, /data-map-load="PUJILI"/);
    assert.doesNotMatch(card, /<iframe|maps.googleapis.com/);
    assert.doesNotMatch(utcMapCard('" onclick="bad'), /onclick/);
});

test('embedded maps load only when the user opens them and keep the external link available', () => {
    let click;
    let loaded;
    const button = { dataset: { mapLoad: 'SALACHE' }, parentElement: { replaceChildren(node) { loaded = node; } } };
    const root = { addEventListener(_event, callback) { click = callback; }, contains: (node) => node === button };
    globalThis.document = { createElement: () => ({}) };
    initChatMaps(root);
    click({ target: { closest: () => null } });
    assert.equal(loaded, undefined);
    click({ target: { closest: () => button } });
    assert.equal(loaded.title, 'Mapa de Campus Salache de la UTC');
    assert.match(loaded.src, /Campus\+Salache&output=embed$/);
});
