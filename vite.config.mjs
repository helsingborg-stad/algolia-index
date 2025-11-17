import { createViteConfig } from "vite-config-factory";

const entries = {
	"css/algolia-index": "./source/sass/algolia-index.scss",
	"js/algolia-index": "./source/js/algolia-index.ts",
};

export default createViteConfig(entries, {
	outDir: "assets/dist",
	manifestFile: "manifest.json",
});
