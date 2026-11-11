import test from 'node:test';
import assert from 'node:assert/strict';

import {
    countStagedVisibleBlocks,
    commitModuleBlockAppendSlot,
    coordinateDeleteThenSave,
    deriveModuleEmptyState,
    deriveStagedAssessmentAttachment,
    moduleBlockAppendPosition,
    releaseModuleBlockAppendSlot,
    remapSurvivingBlockEntries,
    reserveModuleBlockAppendSlot,
} from '../../resources/js/module-save-coordinator.js';

test('module empty state separates real blocks from open add panels', () => {
    const emptyBlocks = [];

    assert.deepEqual(deriveModuleEmptyState(emptyBlocks, []), {
        realBlockCount: 0,
        openPanelCount: 0,
        emptyVisible: true,
    });

    for (const panel of ['media', 'youtube', 'file', 'quiz', 'submission']) {
        const state = deriveModuleEmptyState(emptyBlocks, [panel]);
        assert.equal(state.emptyVisible, false, `${panel} hides the empty placeholder`);
        assert.equal(state.realBlockCount, 0, `${panel} is not a canonical block`);
    }

    const severalOpen = ['media', 'file', 'quiz'];
    assert.equal(deriveModuleEmptyState(emptyBlocks, severalOpen).emptyVisible, false);
    severalOpen.shift();
    assert.equal(deriveModuleEmptyState(emptyBlocks, severalOpen).emptyVisible, false);
    severalOpen.shift();
    assert.equal(deriveModuleEmptyState(emptyBlocks, severalOpen).emptyVisible, false);
    severalOpen.shift();
    assert.equal(deriveModuleEmptyState(emptyBlocks, severalOpen).emptyVisible, true);

    for (const type of ['title', 'text', 'media', 'youtube', 'material', 'quiz', 'submission']) {
        const state = deriveModuleEmptyState([{ type, markedDelete: '' }], []);
        assert.equal(state.emptyVisible, false, `${type} real block hides the empty placeholder`);
        assert.equal(state.realBlockCount, 1);
    }
});

test('open panels never enter the canonical or persisted block collection', () => {
    const persistedBlocks = [];
    const panelOnlyState = deriveModuleEmptyState(persistedBlocks, [
        'media',
        'youtube',
        'file',
        'quiz',
        'submission',
    ]);

    assert.equal(panelOnlyState.realBlockCount, 0);
    assert.equal(countStagedVisibleBlocks(persistedBlocks), 0);
    assert.equal(persistedBlocks.length, 0);

    const reopenedState = deriveModuleEmptyState(persistedBlocks, []);
    assert.equal(reopenedState.emptyVisible, true);
    assert.equal(reopenedState.realBlockCount, 0);
});

test('staged block counts and assessment attachments follow unsaved state', () => {
    const persisted = [
        { assessmentId: '30', moduleId: '10', moduleTitle: 'Chapter 2', markedDelete: '' },
        { assessmentId: '', moduleId: '10', moduleTitle: 'Chapter 2', markedDelete: '' },
    ];

    assert.equal(countStagedVisibleBlocks(persisted), 2);
    assert.deepEqual(deriveStagedAssessmentAttachment(persisted, 30), {
        embedded: true,
        moduleId: '10',
        moduleTitle: 'Chapter 2',
    });

    const removed = persisted.map((block, index) => (
        index === 0 ? { ...block, markedDelete: '1' } : block
    ));
    assert.equal(countStagedVisibleBlocks(removed), 1);
    assert.deepEqual(deriveStagedAssessmentAttachment(removed, 30), {
        embedded: false,
        moduleId: null,
        moduleTitle: null,
    });

    const addedBack = [
        ...removed,
        { assessmentId: '30', moduleId: '10', moduleTitle: 'Chapter 2', markedDelete: '' },
    ];
    assert.equal(countStagedVisibleBlocks(addedBack), 2);
    assert.equal(deriveStagedAssessmentAttachment(addedBack, 30).embedded, true);

    assert.equal(countStagedVisibleBlocks(persisted), 2, 'discard restores the persisted preview');
    assert.equal(deriveStagedAssessmentAttachment(persisted, 30).embedded, true);
});

test('actual active block relationship wins over a conflicting legacy module hint', () => {
    const blocks = [{
        assessmentId: '31',
        moduleId: '10',
        moduleTitle: 'Real Module',
        legacyModuleId: '11',
        markedDelete: '',
    }];

    assert.deepEqual(deriveStagedAssessmentAttachment(blocks, 31), {
        embedded: true,
        moduleId: '10',
        moduleTitle: 'Real Module',
    });
});

function fakeBlock(type) {
    return {
        type,
        dataset: {},
        hidden: false,
        parentNode: null,
        classList: {
            contains: (className) => className === 'lw-block',
        },
    };
}

function fakeSlot() {
    return {
        dataset: {},
        hidden: false,
        parentNode: null,
        classList: {
            contains: () => false,
        },
    };
}

function fakeBlockList() {
    return {
        children: [],
        appendChild(node) {
            node.parentNode = this;
            this.children.push(node);
        },
        replaceChild(node, replaced) {
            const index = this.children.indexOf(replaced);
            assert.notEqual(index, -1);
            replaced.parentNode = null;
            node.parentNode = this;
            this.children.splice(index, 1, node);
        },
        removeChild(node) {
            const index = this.children.indexOf(node);
            assert.notEqual(index, -1);
            node.parentNode = null;
            this.children.splice(index, 1);
        },
    };
}

function appendServerBlock(blockList, type, completeImmediately = true) {
    const slot = reserveModuleBlockAppendSlot(blockList, fakeSlot());
    const position = moduleBlockAppendPosition(blockList, slot);

    if (completeImmediately) {
        commitModuleBlockAppendSlot(blockList, slot, fakeBlock(type));
    }

    return {
        complete() {
            commitModuleBlockAppendSlot(blockList, slot, fakeBlock(type));
        },
        position,
        slot,
    };
}

function blockTypes(blockList) {
    return blockList.children
        .filter((child) => child.classList.contains('lw-block'))
        .map((child) => child.type);
}

test('all seven block types retain the exact order in which they are added', () => {
    const blockList = fakeBlockList();
    const expectedOrder = [];

    for (const type of [
        'title',
        'text',
        'media',
        'youtube',
        'material',
        'quiz',
        'submission',
    ]) {
        expectedOrder.push(type);

        if (type === 'title' || type === 'text') {
            blockList.appendChild(fakeBlock(type));
        } else {
            appendServerBlock(blockList, type);
        }

        assert.deepEqual(blockTypes(blockList), expectedOrder);
    }

    assert.equal(blockTypes(blockList).indexOf('material'), 4);
});

test('file blocks have no special placement when added last or in the middle', () => {
    const fileLast = fakeBlockList();

    for (const type of ['title', 'text', 'media', 'youtube', 'quiz', 'submission']) {
        if (type === 'title' || type === 'text') {
            fileLast.appendChild(fakeBlock(type));
        } else {
            appendServerBlock(fileLast, type);
        }
    }

    appendServerBlock(fileLast, 'material');
    assert.deepEqual(blockTypes(fileLast), [
        'title',
        'text',
        'media',
        'youtube',
        'quiz',
        'submission',
        'material',
    ]);

    const fileInMiddle = fakeBlockList();
    fileInMiddle.appendChild(fakeBlock('text'));
    appendServerBlock(fileInMiddle, 'quiz');
    appendServerBlock(fileInMiddle, 'material');
    appendServerBlock(fileInMiddle, 'media');
    appendServerBlock(fileInMiddle, 'submission');

    assert.deepEqual(blockTypes(fileInMiddle), [
        'text',
        'quiz',
        'material',
        'media',
        'submission',
    ]);
});

test('async file and media completion replaces their reserved positions in place', () => {
    const blockList = fakeBlockList();
    blockList.appendChild(fakeBlock('text'));
    appendServerBlock(blockList, 'quiz');

    const fileRequest = appendServerBlock(blockList, 'material', false);
    const mediaRequest = appendServerBlock(blockList, 'media', false);
    appendServerBlock(blockList, 'submission');

    assert.equal(fileRequest.position, 3);
    assert.equal(mediaRequest.position, 4);

    mediaRequest.complete();
    fileRequest.complete();

    assert.deepEqual(blockTypes(blockList), [
        'text',
        'quiz',
        'material',
        'media',
        'submission',
    ]);

    const failedRequest = appendServerBlock(blockList, 'youtube', false);
    releaseModuleBlockAppendSlot(blockList, failedRequest.slot);
    assert.deepEqual(blockTypes(blockList), [
        'text',
        'quiz',
        'material',
        'media',
        'submission',
    ]);
});

function deferred() {
    let resolve;
    let reject;

    const promise = new Promise((resolvePromise, rejectPromise) => {
        resolve = resolvePromise;
        reject = rejectPromise;
    });

    return {
        promise,
        resolve,
        reject,
    };
}

function coordinatorOptions(overrides = {}) {
    return {
        deletionTasks: [],
        isDeletionSuccessful: (result) => result.ok === true,
        finalizeDeletion: () => {},
        createSavePayload: () => ({ payload: true }),
        saveBatch: async () => ({ ok: true }),
        isSaveSuccessful: (result) => result.ok === true,
        ...overrides,
    };
}

test('successful batch save opens the deletion gate and finalizes after every deletion', async () => {
    const batchSave = deferred();
    const firstDeletion = deferred();
    const secondDeletion = deferred();
    const events = [];

    const operation = coordinateDeleteThenSave(coordinatorOptions({
        createSavePayload: () => {
            events.push('create-payload');

            return { module: 42 };
        },
        saveBatch: async (payload) => {
            events.push('save-batch');
            assert.deepEqual(payload, { module: 42 });

            return batchSave.promise;
        },
        deletionTasks: [
            () => {
                events.push('delete-1-started');

                return firstDeletion.promise;
            },
            () => {
                events.push('delete-2-started');

                return secondDeletion.promise;
            },
        ],
        finalizeDeletion: (result) => {
            events.push(`finalize-${result.id}`);
        },
    }));

    assert.deepEqual(events, [
        'create-payload',
        'save-batch',
    ]);

    batchSave.resolve({ ok: true, saved: true });

    await new Promise((resolve) => setImmediate(resolve));

    assert.deepEqual(events, [
        'create-payload',
        'save-batch',
        'delete-1-started',
        'delete-2-started',
    ]);

    firstDeletion.resolve({ ok: true, id: 1 });

    await Promise.resolve();
    await Promise.resolve();

    assert.deepEqual(events, [
        'create-payload',
        'save-batch',
        'delete-1-started',
        'delete-2-started',
    ]);

    secondDeletion.resolve({ ok: true, id: 2 });

    const result = await operation;

    assert.deepEqual(events, [
        'create-payload',
        'save-batch',
        'delete-1-started',
        'delete-2-started',
        'finalize-1',
        'finalize-2',
    ]);

    assert.deepEqual(result, {
        ok: true,
        phase: 'complete',
        deletionResults: [
            { ok: true, id: 1 },
            { ok: true, id: 2 },
        ],
        saveResult: {
            ok: true,
            saved: true,
        },
    });
});

test('unsuccessful batch result prevents deletion and finalization', async () => {
    let deletionTaskStarts = 0;
    let finalizationCount = 0;
    let payloadCount = 0;
    let saveCount = 0;

    const saveResult = {
        ok: false,
        message: 'Synthetic batch validation failure',
    };

    const result = await coordinateDeleteThenSave(coordinatorOptions({
        deletionTasks: [
            async () => {
                deletionTaskStarts += 1;

                return { ok: true, id: 1 };
            },
            async () => {
                deletionTaskStarts += 1;

                return { ok: true, id: 2 };
            },
        ],
        finalizeDeletion: () => {
            finalizationCount += 1;
        },
        createSavePayload: () => {
            payloadCount += 1;

            return {};
        },
        saveBatch: async () => {
            saveCount += 1;

            return saveResult;
        },
    }));

    assert.equal(payloadCount, 1);
    assert.equal(saveCount, 1);
    assert.equal(deletionTaskStarts, 0);
    assert.equal(finalizationCount, 0);

    assert.deepEqual(result, {
        ok: false,
        phase: 'save',
        deletionResults: [],
        saveResult,
    });
});

test('batch rejection propagates before any deletion starts', async () => {
    const expectedError = new Error('Synthetic rejected batch save');
    let deletionTaskStarts = 0;
    let finalizationCount = 0;
    let payloadCount = 0;
    let saveCount = 0;

    const operation = coordinateDeleteThenSave(coordinatorOptions({
        deletionTasks: [
            async () => {
                deletionTaskStarts += 1;

                return { ok: true, id: 1 };
            },
            async () => {
                deletionTaskStarts += 1;

                return { ok: true, id: 2 };
            },
        ],
        finalizeDeletion: () => {
            finalizationCount += 1;
        },
        createSavePayload: () => {
            payloadCount += 1;

            return {};
        },
        saveBatch: async () => {
            saveCount += 1;

            throw expectedError;
        },
    }));

    await assert.rejects(operation, (error) => error === expectedError);

    assert.equal(payloadCount, 1);
    assert.equal(saveCount, 1);
    assert.equal(deletionTaskStarts, 0);
    assert.equal(finalizationCount, 0);
});

test('unsuccessful deletion result is reported after a successful batch save', async () => {
    let deletionTaskStarts = 0;
    let finalizationCount = 0;
    let payloadCount = 0;
    let saveCount = 0;

    const saveResult = {
        ok: true,
        saved: true,
    };

    const failedDeletion = {
        ok: false,
        id: 2,
        message: 'Synthetic deletion failure',
    };

    const result = await coordinateDeleteThenSave(coordinatorOptions({
        deletionTasks: [
            async () => {
                deletionTaskStarts += 1;

                return { ok: true, id: 1 };
            },
            async () => {
                deletionTaskStarts += 1;

                return failedDeletion;
            },
        ],
        finalizeDeletion: () => {
            finalizationCount += 1;
        },
        createSavePayload: () => {
            payloadCount += 1;

            return { module: 42 };
        },
        saveBatch: async () => {
            saveCount += 1;

            return saveResult;
        },
    }));

    assert.equal(payloadCount, 1);
    assert.equal(saveCount, 1);
    assert.equal(deletionTaskStarts, 2);
    assert.equal(finalizationCount, 0);
    assert.equal(result.ok, false);
    assert.equal(result.phase, 'delete');
    assert.equal(result.failedDeletionIndex, 1);
    assert.equal(result.failedDeletion, failedDeletion);
    assert.equal(result.saveResult, saveResult);
});

test('deletion rejection propagates after a successful batch save', async () => {
    const expectedError = new Error('Synthetic rejected deletion');
    const events = [];
    let finalizationCount = 0;

    const operation = coordinateDeleteThenSave(coordinatorOptions({
        createSavePayload: () => {
            events.push('create-payload');

            return { module: 42 };
        },
        saveBatch: async () => {
            events.push('save-batch');

            return { ok: true, saved: true };
        },
        deletionTasks: [
            async () => {
                events.push('delete-1-started');

                return { ok: true, id: 1 };
            },
            async () => {
                events.push('delete-2-started');

                throw expectedError;
            },
        ],
        finalizeDeletion: () => {
            finalizationCount += 1;
        },
    }));

    await assert.rejects(operation, (error) => error === expectedError);

    assert.deepEqual(events, [
        'create-payload',
        'save-batch',
        'delete-1-started',
        'delete-2-started',
    ]);
    assert.equal(finalizationCount, 0);
});

test('batch-save failure prevents every deletion task from starting', async () => {
    let deletionTaskStarts = 0;
    let finalizationCount = 0;
    let saveCount = 0;

    const saveResult = {
        ok: false,
        message: 'Synthetic batch validation failure',
    };

    const result = await coordinateDeleteThenSave(coordinatorOptions({
        deletionTasks: [
            async () => {
                deletionTaskStarts += 1;

                return { ok: true, id: 1 };
            },
            async () => {
                deletionTaskStarts += 1;

                return { ok: true, id: 2 };
            },
        ],
        finalizeDeletion: () => {
            finalizationCount += 1;
        },
        saveBatch: async () => {
            saveCount += 1;

            return saveResult;
        },
    }));

    assert.equal(saveCount, 1);
    assert.equal(deletionTaskStarts, 0);
    assert.equal(finalizationCount, 0);
    assert.equal(result.ok, false);
    assert.equal(result.phase, 'save');
    assert.equal(result.saveResult, saveResult);
});

test('surviving block entries are compacted while marked block entries are omitted', () => {
    const entries = [
        ['_token', 'synthetic-csrf-token'],
        ['_method', 'PATCH'],
        ['module_title', 'Synthetic Module'],
        ['blocks[0][block_id]', '10'],
        ['blocks[0][block_title]', 'Keep First'],
        ['blocks[1][block_id]', '11'],
        ['blocks[1][block_title]', 'Remove This'],
        ['blocks[2][block_id]', '12'],
        ['blocks[2][block_title]', 'Keep Last'],
    ];

    const result = remapSurvivingBlockEntries(entries, [0, 2]);

    assert.deepEqual(result, [
        ['_token', 'synthetic-csrf-token'],
        ['_method', 'PATCH'],
        ['module_title', 'Synthetic Module'],
        ['blocks[0][block_id]', '10'],
        ['blocks[0][block_title]', 'Keep First'],
        ['blocks[1][block_id]', '12'],
        ['blocks[1][block_title]', 'Keep Last'],
    ]);
});

test('remapping preserves repeated fields and non-string values', () => {
    const syntheticFile = {
        name: 'synthetic-material.pdf',
    };

    const entries = [
        ['blocks[4][accepted_format][]', 'pdf'],
        ['blocks[4][accepted_format][]', 'docx'],
        ['blocks[4][material_file]', syntheticFile],
    ];

    const result = remapSurvivingBlockEntries(entries, [4]);

    assert.deepEqual(result, [
        ['blocks[0][accepted_format][]', 'pdf'],
        ['blocks[0][accepted_format][]', 'docx'],
        ['blocks[0][material_file]', syntheticFile],
    ]);

    assert.equal(result[2][1], syntheticFile);
});

test('an empty surviving-block list retains non-block fields only', () => {
    const entries = [
        ['_token', 'synthetic-csrf-token'],
        ['module_title', 'Synthetic Empty Module'],
        ['blocks[0][block_id]', '10'],
        ['blocks[0][block_title]', 'Remove First'],
        ['blocks[1][block_id]', '11'],
        ['blocks[1][block_title]', 'Remove Second'],
    ];

    const result = remapSurvivingBlockEntries(entries, []);

    assert.deepEqual(result, [
        ['_token', 'synthetic-csrf-token'],
        ['module_title', 'Synthetic Empty Module'],
    ]);
});
