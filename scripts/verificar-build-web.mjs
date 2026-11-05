import fs from 'fs'
import path from 'path'

const root = path.resolve('dist-web')
const html = fs.readFileSync(path.join(root, 'index.html'), 'utf8')
const match = html.match(/assets\/index-[^"]+\.js/)
if (!match) {
  console.error('sin bundle js')
  process.exit(1)
}
const js = fs.readFileSync(path.join(root, match[0]), 'utf8')
console.log('api', js.includes('https://3bigotesbarberie.com/public'))
console.log('opt', fs.existsSync(path.join(root, 'media/fondo-opt.mp4')))
console.log('poster', fs.existsSync(path.join(root, 'media/fondo-poster.jpg')))
console.log('js', match[0])
