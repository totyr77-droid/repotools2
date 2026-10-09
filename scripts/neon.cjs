const path = require('node:path');
const { spawn } = require('node:child_process');
const { createInterface } = require('node:readline');

const root = path.resolve(__dirname, '..');
const runtime = path.join(root, 'node_modules', 'node', 'bin', process.platform === 'win32' ? 'node.exe' : 'node');
const cli = path.join(root, 'node_modules', 'neon', 'dist', 'cli.js');
const env = { ...process.env };
const pathKey = Object.keys(env).find(key => key.toLowerCase() === 'path') || 'PATH';
env[pathKey] = [path.dirname(runtime), ...(process.platform === 'win32' ? ['C:\\Program Files\\Git\\cmd'] : []), env[pathKey]].filter(Boolean).join(path.delimiter);
const child = spawn(runtime, [cli, ...process.argv.slice(2)], { cwd: root, env, stdio: ['inherit', 'pipe', 'pipe'] });
for (const [stream, output] of [[child.stdout, process.stdout], [child.stderr, process.stderr]]) {
  createInterface({ input: stream }).on('line', line => {
    if (!line.startsWith('DEBUG:')) output.write(line.replace(/postgres(?:ql)?:\/\/\S+/g, '[conexión protegida]') + '\n');
  });
}
child.on('error', error => { console.error(error.message); process.exitCode = 1; });
child.on('exit', code => { process.exitCode = code ?? 1; });
