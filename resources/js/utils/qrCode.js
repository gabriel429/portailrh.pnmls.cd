const VERSION = 5
const SIZE = 21 + VERSION * 4
const DATA_CODEWORDS = 108
const ECC_CODEWORDS = 26
const ECL_FORMAT_BITS = 1

function appendBits(buffer, value, length) {
    for (let i = length - 1; i >= 0; i--) {
        buffer.push((value >>> i) & 1)
    }
}

function bitsToBytes(bits) {
    const bytes = []
    for (let i = 0; i < bits.length; i += 8) {
        let value = 0
        for (let j = 0; j < 8; j++) {
            value = (value << 1) | (bits[i + j] || 0)
        }
        bytes.push(value)
    }
    return bytes
}

function gfMultiply(x, y) {
    let z = 0
    for (let i = 7; i >= 0; i--) {
        z = (z << 1) ^ ((z >>> 7) * 0x11d)
        z ^= ((y >>> i) & 1) * x
    }
    return z & 0xff
}

function reedSolomonDivisor(degree) {
    const result = new Array(degree).fill(0)
    result[degree - 1] = 1
    let root = 1

    for (let i = 0; i < degree; i++) {
        for (let j = 0; j < result.length; j++) {
            result[j] = gfMultiply(result[j], root)
            if (j + 1 < result.length) {
                result[j] ^= result[j + 1]
            }
        }
        root = gfMultiply(root, 0x02)
    }

    return result
}

function reedSolomonRemainder(data, divisor) {
    const result = new Array(divisor.length).fill(0)
    for (const byte of data) {
        const factor = byte ^ result.shift()
        result.push(0)
        for (let i = 0; i < divisor.length; i++) {
            result[i] ^= gfMultiply(divisor[i], factor)
        }
    }
    return result
}

function encodeCodewords(text) {
    const bytes = Array.from(new TextEncoder().encode(text))
    const bits = []

    if (bytes.length > 106) {
        throw new Error('Le texte du QR code est trop long.')
    }

    appendBits(bits, 0x4, 4)
    appendBits(bits, bytes.length, 8)
    for (const byte of bytes) appendBits(bits, byte, 8)

    const capacity = DATA_CODEWORDS * 8
    appendBits(bits, 0, Math.min(4, capacity - bits.length))
    while (bits.length % 8 !== 0) bits.push(0)

    const data = bitsToBytes(bits)
    for (let pad = 0xec; data.length < DATA_CODEWORDS; pad ^= 0xfd) {
        data.push(pad)
    }

    const ecc = reedSolomonRemainder(data, reedSolomonDivisor(ECC_CODEWORDS))
    return data.concat(ecc)
}

function emptyMatrix() {
    return {
        modules: Array.from({ length: SIZE }, () => Array(SIZE).fill(false)),
        reserved: Array.from({ length: SIZE }, () => Array(SIZE).fill(false)),
    }
}

function setFunction(matrix, x, y, value) {
    if (x < 0 || y < 0 || x >= SIZE || y >= SIZE) return
    matrix.modules[y][x] = Boolean(value)
    matrix.reserved[y][x] = true
}

function drawFinder(matrix, cx, cy) {
    for (let dy = -4; dy <= 4; dy++) {
        for (let dx = -4; dx <= 4; dx++) {
            const x = cx + dx
            const y = cy + dy
            const dist = Math.max(Math.abs(dx), Math.abs(dy))
            setFunction(matrix, x, y, dist !== 2 && dist !== 4)
        }
    }
}

function drawAlignment(matrix, cx, cy) {
    for (let dy = -2; dy <= 2; dy++) {
        for (let dx = -2; dx <= 2; dx++) {
            const dist = Math.max(Math.abs(dx), Math.abs(dy))
            setFunction(matrix, cx + dx, cy + dy, dist !== 1)
        }
    }
}

function drawFunctionPatterns(matrix) {
    drawFinder(matrix, 3, 3)
    drawFinder(matrix, SIZE - 4, 3)
    drawFinder(matrix, 3, SIZE - 4)
    drawAlignment(matrix, 30, 30)

    for (let i = 0; i < SIZE; i++) {
        if (!matrix.reserved[6][i]) setFunction(matrix, i, 6, i % 2 === 0)
        if (!matrix.reserved[i][6]) setFunction(matrix, 6, i, i % 2 === 0)
    }

    for (let i = 0; i < 8; i++) {
        setFunction(matrix, 8, i, false)
        setFunction(matrix, i, 8, false)
        setFunction(matrix, SIZE - 1 - i, 8, false)
        setFunction(matrix, 8, SIZE - 1 - i, false)
    }
    setFunction(matrix, 8, 8, false)
    setFunction(matrix, 8, SIZE - 8, true)
}

function drawCodewords(matrix, codewords) {
    const bits = []
    for (const codeword of codewords) appendBits(bits, codeword, 8)

    let bitIndex = 0
    let upward = true
    for (let right = SIZE - 1; right >= 1; right -= 2) {
        if (right === 6) right--
        for (let vert = 0; vert < SIZE; vert++) {
            const y = upward ? SIZE - 1 - vert : vert
            for (let j = 0; j < 2; j++) {
                const x = right - j
                if (!matrix.reserved[y][x]) {
                    matrix.modules[y][x] = Boolean(bits[bitIndex] || 0)
                    bitIndex++
                }
            }
        }
        upward = !upward
    }
}

function maskBit(mask, x, y) {
    switch (mask) {
        case 0: return (x + y) % 2 === 0
        case 1: return y % 2 === 0
        case 2: return x % 3 === 0
        case 3: return (x + y) % 3 === 0
        case 4: return (Math.floor(y / 2) + Math.floor(x / 3)) % 2 === 0
        case 5: return ((x * y) % 2) + ((x * y) % 3) === 0
        case 6: return (((x * y) % 2) + ((x * y) % 3)) % 2 === 0
        case 7: return (((x + y) % 2) + ((x * y) % 3)) % 2 === 0
        default: return false
    }
}

function cloneMatrix(matrix) {
    return {
        modules: matrix.modules.map(row => row.slice()),
        reserved: matrix.reserved.map(row => row.slice()),
    }
}

function applyMask(matrix, mask) {
    for (let y = 0; y < SIZE; y++) {
        for (let x = 0; x < SIZE; x++) {
            if (!matrix.reserved[y][x] && maskBit(mask, x, y)) {
                matrix.modules[y][x] = !matrix.modules[y][x]
            }
        }
    }
}

function formatBits(mask) {
    let data = (ECL_FORMAT_BITS << 3) | mask
    let rem = data
    for (let i = 0; i < 10; i++) {
        rem = (rem << 1) ^ (((rem >>> 9) & 1) * 0x537)
    }
    return ((data << 10) | rem) ^ 0x5412
}

function drawFormatBits(matrix, mask) {
    const bits = formatBits(mask)
    const bit = i => ((bits >>> i) & 1) !== 0

    for (let i = 0; i <= 5; i++) setFunction(matrix, 8, i, bit(i))
    setFunction(matrix, 8, 7, bit(6))
    setFunction(matrix, 8, 8, bit(7))
    setFunction(matrix, 7, 8, bit(8))
    for (let i = 9; i < 15; i++) setFunction(matrix, 14 - i, 8, bit(i))

    for (let i = 0; i < 8; i++) setFunction(matrix, SIZE - 1 - i, 8, bit(i))
    for (let i = 8; i < 15; i++) setFunction(matrix, 8, SIZE - 15 + i, bit(i))
    setFunction(matrix, 8, SIZE - 8, true)
}

function penalty(matrix) {
    let score = 0

    for (let y = 0; y < SIZE; y++) {
        let runColor = matrix.modules[y][0]
        let run = 1
        for (let x = 1; x < SIZE; x++) {
            if (matrix.modules[y][x] === runColor) run++
            else {
                if (run >= 5) score += run - 2
                runColor = matrix.modules[y][x]
                run = 1
            }
        }
        if (run >= 5) score += run - 2
    }

    for (let x = 0; x < SIZE; x++) {
        let runColor = matrix.modules[0][x]
        let run = 1
        for (let y = 1; y < SIZE; y++) {
            if (matrix.modules[y][x] === runColor) run++
            else {
                if (run >= 5) score += run - 2
                runColor = matrix.modules[y][x]
                run = 1
            }
        }
        if (run >= 5) score += run - 2
    }

    for (let y = 0; y < SIZE - 1; y++) {
        for (let x = 0; x < SIZE - 1; x++) {
            const color = matrix.modules[y][x]
            if (color === matrix.modules[y][x + 1] && color === matrix.modules[y + 1][x] && color === matrix.modules[y + 1][x + 1]) {
                score += 3
            }
        }
    }

    const finderPattern = [true, false, true, true, true, false, true, false, false, false, false]
    const reversePattern = [false, false, false, false, true, false, true, true, true, false, true]
    const matchesPattern = (line, i, pattern) => pattern.every((value, offset) => line[i + offset] === value)

    for (let y = 0; y < SIZE; y++) {
        for (let x = 0; x <= SIZE - 11; x++) {
            if (matchesPattern(matrix.modules[y], x, finderPattern) || matchesPattern(matrix.modules[y], x, reversePattern)) score += 40
        }
    }
    for (let x = 0; x < SIZE; x++) {
        const column = matrix.modules.map(row => row[x])
        for (let y = 0; y <= SIZE - 11; y++) {
            if (matchesPattern(column, y, finderPattern) || matchesPattern(column, y, reversePattern)) score += 40
        }
    }

    const total = SIZE * SIZE
    const dark = matrix.modules.flat().filter(Boolean).length
    score += Math.floor(Math.abs(dark * 20 - total * 10) / total) * 10

    return score
}

function makeMatrix(text) {
    const base = emptyMatrix()
    drawFunctionPatterns(base)
    drawCodewords(base, encodeCodewords(text))

    let best = null
    let bestScore = Infinity
    for (let mask = 0; mask < 8; mask++) {
        const candidate = cloneMatrix(base)
        applyMask(candidate, mask)
        drawFormatBits(candidate, mask)
        const score = penalty(candidate)
        if (score < bestScore) {
            best = candidate
            bestScore = score
        }
    }
    return best.modules
}

export function qrToSvgDataUri(text, options = {}) {
    const dark = options.dark || '#0f172a'
    const light = options.light || '#ffffff'
    const margin = Number.isFinite(options.margin) ? options.margin : 3
    const modules = makeMatrix(String(text || ''))
    const fullSize = SIZE + margin * 2

    let path = ''
    for (let y = 0; y < SIZE; y++) {
        for (let x = 0; x < SIZE; x++) {
            if (modules[y][x]) path += `M${x + margin},${y + margin}h1v1h-1z`
        }
    }

    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${fullSize} ${fullSize}" shape-rendering="crispEdges"><path fill="${light}" d="M0 0h${fullSize}v${fullSize}H0z"/><path fill="${dark}" d="${path}"/></svg>`
    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`
}
