export function remapSurvivingBlockEntries(
    entries,
    survivingBlockIndexes,
) {
    const compactIndexByOriginalIndex = new Map(
        survivingBlockIndexes.map((originalIndex, compactIndex) => [
            String(originalIndex),
            compactIndex,
        ]),
    );

    const remappedEntries = [];

    for (const [name, value] of entries) {
        const blockFieldMatch = /^blocks\[(\d+)\](.*)$/.exec(name);

        if (!blockFieldMatch) {
            remappedEntries.push([name, value]);
            continue;
        }

        const compactIndex = compactIndexByOriginalIndex.get(
            blockFieldMatch[1],
        );

        if (compactIndex === undefined) {
            continue;
        }

        remappedEntries.push([
            `blocks[${compactIndex}]${blockFieldMatch[2]}`,
            value,
        ]);
    }

    return remappedEntries;
}

export function reserveModuleBlockAppendSlot(blockList, slot) {
    slot.dataset.blockAppendSlot = '1';
    slot.hidden = true;
    blockList.appendChild(slot);

    return slot;
}

export function moduleBlockAppendPosition(blockList, slot) {
    let position = 0;

    for (const child of Array.from(blockList.children)) {
        const isActiveBlock = child.classList?.contains('lw-block')
            && child.dataset?.markedDelete !== '1';
        const isAppendSlot = child.dataset?.blockAppendSlot === '1';

        if (!isActiveBlock && !isAppendSlot) {
            continue;
        }

        position += 1;

        if (child === slot) {
            return position;
        }
    }

    return position + 1;
}

export function commitModuleBlockAppendSlot(blockList, slot, block) {
    if (slot.parentNode === blockList) {
        blockList.replaceChild(block, slot);
    } else {
        blockList.appendChild(block);
    }

    return block;
}

export function releaseModuleBlockAppendSlot(blockList, slot) {
    if (slot.parentNode === blockList) {
        blockList.removeChild(slot);
    }
}

export function countStagedVisibleBlocks(blocks) {
    return Array.from(blocks).filter(
        (block) => String(block.markedDelete ?? block.dataset?.markedDelete ?? '') !== '1',
    ).length;
}

export function deriveModuleEmptyState(blocks, openPanels = []) {
    const realBlockCount = countStagedVisibleBlocks(blocks);
    const openPanelCount = typeof openPanels === 'number'
        ? openPanels
        : Array.from(openPanels).length;

    return {
        realBlockCount,
        openPanelCount,
        emptyVisible: realBlockCount === 0 && openPanelCount === 0,
    };
}

export function deriveStagedAssessmentAttachment(blocks, assessmentId) {
    const requestedId = String(assessmentId);
    const attachedBlock = Array.from(blocks).find((block) => (
        String(block.assessmentId ?? block.dataset?.assessmentId ?? '') === requestedId
        && String(block.markedDelete ?? block.dataset?.markedDelete ?? '') !== '1'
    ));

    if (!attachedBlock) {
        return {
            embedded: false,
            moduleId: null,
            moduleTitle: null,
        };
    }

    return {
        embedded: true,
        moduleId: String(attachedBlock.moduleId ?? ''),
        moduleTitle: attachedBlock.moduleTitle ?? null,
    };
}

export async function coordinateDeleteThenSave({
    deletionTasks,
    isDeletionSuccessful,
    finalizeDeletion,
    createSavePayload,
    saveBatch,
    isSaveSuccessful,
}) {
    const savePayload = createSavePayload();
    const saveResult = await saveBatch(savePayload);

    if (!isSaveSuccessful(saveResult)) {
        return {
            ok: false,
            phase: 'save',
            deletionResults: [],
            saveResult,
        };
    }

    const deletionResults = await Promise.all(
        deletionTasks.map((deleteTask) => deleteTask()),
    );

    const failedDeletionIndex = deletionResults.findIndex(
        (result) => !isDeletionSuccessful(result),
    );

    if (failedDeletionIndex !== -1) {
        return {
            ok: false,
            phase: 'delete',
            deletionResults,
            failedDeletion: deletionResults[failedDeletionIndex],
            failedDeletionIndex,
            saveResult,
        };
    }

    deletionResults.forEach((result, index) => {
        finalizeDeletion(result, index);
    });

    return {
        ok: true,
        phase: 'complete',
        deletionResults,
        saveResult,
    };
}

if (typeof window !== 'undefined') {
    window.coordinateDeleteThenSave = coordinateDeleteThenSave;
    window.remapSurvivingBlockEntries = remapSurvivingBlockEntries;
    window.reserveModuleBlockAppendSlot = reserveModuleBlockAppendSlot;
    window.moduleBlockAppendPosition = moduleBlockAppendPosition;
    window.commitModuleBlockAppendSlot = commitModuleBlockAppendSlot;
    window.releaseModuleBlockAppendSlot = releaseModuleBlockAppendSlot;
    window.countStagedVisibleBlocks = countStagedVisibleBlocks;
    window.deriveModuleEmptyState = deriveModuleEmptyState;
    window.deriveStagedAssessmentAttachment = deriveStagedAssessmentAttachment;
}
