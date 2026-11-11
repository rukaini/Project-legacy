import { readdir } from 'node:fs/promises';

const testDirectory = new URL('./', import.meta.url);
const files = (await readdir(testDirectory))
    .filter((file) => file.endsWith('.test.js'))
    .sort();

for (const file of files) {
    await import(new URL(file, testDirectory));
}
