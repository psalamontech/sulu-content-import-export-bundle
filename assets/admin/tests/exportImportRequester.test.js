const assert = require('assert');
const fs = require('fs');
const path = require('path');

function loadModule(mocks) {
    const filePath = path.join(__dirname, '..', 'services', 'exportImportRequester.js');
    let source = fs.readFileSync(filePath, 'utf8');

    source = source.replace(
        "import {Requester} from 'sulu-admin-bundle/services';",
        'const {Requester} = mocks;'
    );
    source = source.replace('export function getJson(url) {', 'function getJson(url) {');
    source = source.replace('export function postProtectedJson(url, payload) {', 'function postProtectedJson(url, payload) {');
    source += '\nmodule.exports = {getJson, postProtectedJson};\n';

    const module = {exports: {}};
    const factory = new Function('module', 'exports', 'mocks', 'window', source);
    const windowMock = {
        crypto: {
            getRandomValues(bytes) {
                for (let i = 0; i < bytes.length; i++) {
                    bytes[i] = i + 1;
                }
            },
        },
    };

    factory(module, module.exports, mocks, windowMock);

    return module.exports;
}

async function testGetJsonUsesRequesterAndParsesResponse() {
    let receivedUrl;
    let receivedInit;
    const {getJson} = loadModule({
        Requester: {
            fetch(url, init) {
                receivedUrl = url;
                receivedInit = init;

                return Promise.resolve({
                    ok: true,
                    text: () => Promise.resolve('{"success":true}'),
                });
            },
        },
    });

    const result = await getJson('/admin/content-json/page/123?locale=en');

    assert.deepStrictEqual(result, {success: true});
    assert.strictEqual(receivedUrl, '/admin/content-json/page/123?locale=en');
    assert.deepStrictEqual(receivedInit, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
}

async function testPostProtectedJsonAddsMatchingTokenToHeaderAndPayload() {
    let receivedInit;
    const {postProtectedJson} = loadModule({
        Requester: {
            fetch(_url, init) {
                receivedInit = init;

                return Promise.resolve({
                    ok: true,
                    text: () => Promise.resolve('{}'),
                });
            },
        },
    });

    await postProtectedJson('/admin/content-json/page/123', {content: {title: 'Hello'}});

    const body = JSON.parse(receivedInit.body);

    assert.strictEqual(receivedInit.method, 'POST');
    assert.strictEqual(body._token, receivedInit.headers['csrf-token']);
    assert.ok(body._token.length >= 24);
    assert.deepStrictEqual(body.content, {title: 'Hello'});
    assert.strictEqual(receivedInit.headers['Content-Type'], 'application/json');
    assert.strictEqual(receivedInit.headers['X-Requested-With'], 'XMLHttpRequest');
}

async function testInvalidCsrfGetsFriendlyErrorMessage() {
    const {postProtectedJson} = loadModule({
        Requester: {
            fetch() {
                return Promise.resolve({
                    ok: false,
                    status: 403,
                    text: () => Promise.resolve('{"error":"Invalid CSRF token."}'),
                });
            },
        },
    });

    await assert.rejects(
        () => postProtectedJson('/admin/content-json/page/123', {content: {}}),
        /session expired|security token became invalid/i
    );
}

async function run() {
    await testGetJsonUsesRequesterAndParsesResponse();
    await testPostProtectedJsonAddsMatchingTokenToHeaderAndPayload();
    await testInvalidCsrfGetsFriendlyErrorMessage();
    console.log('exportImportRequester tests passed');
}

run().catch((error) => {
    console.error(error);
    process.exit(1);
});
