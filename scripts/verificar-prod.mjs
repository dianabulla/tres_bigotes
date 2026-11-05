import { execFileSync } from 'child_process'

function curl(args) {
  return execFileSync('curl.exe', ['-sS', '--max-time', '30', ...args], { encoding: 'utf8' })
}

const home = curl(['https://3bigotesbarberie.com/'])
const js = home.match(/assets\/index-[^"]+\.js/)
console.log('home_js', js ? js[0] : 'NO')
console.log('mentions_opt', home.includes('fondo-opt') || /index-Cw6NMJhC/.test(home))

const head = (url) => {
  const out = execFileSync(
    'curl.exe',
    ['-sS', '-o', 'NUL', '-w', '%{http_code} %{size_download}', '--max-time', '30', url],
    { encoding: 'utf8' },
  )
  console.log(url, out)
}

head('https://3bigotesbarberie.com/assets/index-Cw6NMJhC.js')
head('https://3bigotesbarberie.com/media/fondo-opt.mp4')
head('https://3bigotesbarberie.com/media/fondo-poster.jpg')
console.log('salud', curl(['https://3bigotesbarberie.com/public/api/salud']))
console.log(
  'horarios',
  curl(['https://3bigotesbarberie.com/public/api/reserva/horarios?fecha=2026-10-22&servicios=']).slice(0, 120),
)
