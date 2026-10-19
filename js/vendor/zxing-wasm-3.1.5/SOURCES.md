# ZXing-WASM reader 3.1.5

- Package: https://registry.npmjs.org/zxing-wasm/-/zxing-wasm-3.1.5.tgz
- Upstream: https://github.com/Sec-ant/zxing-wasm
- API documentation: https://sec-ant.github.io/zxing-wasm/docs/
- ZXing-C++ commit embedded by the package: 2ecec3f5be0ee803f6e14a5a2c7028c0cfe525b4
- Wrapper license: LICENSE (MIT).
- Decoder license: LICENSE-zxing-cpp (Apache-2.0), from the exact embedded commit.

Files are copied from the published reader-only build, without rebuilding or editing:

| File | SHA-256 |
|---|---|
| reader.js | 228e6d8ccb841c544386eeafd5f18294d3e4aa0e7aa20419bc51a0009b9d3c17 |
| zxing_reader.wasm | aecc1876de036c62c8419f67a5e1a16b1698a325bcd190aa84810d516e263931 |

The WASM hash also matches the package's exported ZXING_WASM_SHA256. Repotools serves both files locally and overrides locateFile before initialization; the upstream default CDN is not used. Only QR detection is enabled in the rescue worker. Client frames are transferred in memory to that worker and never uploaded.
