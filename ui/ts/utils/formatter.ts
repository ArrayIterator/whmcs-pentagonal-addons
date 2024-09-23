/**
 * Convert Blob to String
 * @param {any|Blob} b
 */
export const BlobToString = (b: any) : string => {
    if (!(b instanceof Blob)) {
        return '';
    }
    let u = URL.createObjectURL(b);
    let x = new XMLHttpRequest();
    x.open('GET', u, false); // although sync, you're not fetching over internet
    x.send();
    URL.revokeObjectURL(u);
    return x.responseText;
}

/**
 * Convert any value to string
 *
 * @param {any} param
 */
export const StrVal = (param: any) : string => {
    if (typeof param === 'number') {
        return param.toString();
    }
    if (typeof param === 'boolean') {
        return param ? '1' : '0';
    }
    if (param === null || param === undefined) {
        return '';
    }
    if (!param || typeof param === 'object' && Object.keys(param).length === 0) {
        return '';
    }
    if (typeof param === 'string') {
        return param;
    }
    if (param instanceof Blob) {
        return BlobToString(param);
    }
    if (ArrayBuffer.isView(param)) {
        param = new Uint8Array(param.buffer, param.byteOffset, param.byteLength);
    }
    if (param instanceof Uint8Array) {
        return String.fromCharCode(...param);
    }
    try {
        return param.toString();
    } catch (e) {
        return '';
    }
}

export const IntVal = (param: any) : number => parseInt(StrVal(param), 10);

/**
 * Format File Size
 *
 * @param {number|bigint} bytes
 * @param {number} decimals
 */
export const FormatFileSize = (bytes: number|bigint, decimals: number = 2): string => {
    bytes = typeof bytes === 'number' || typeof bytes === 'bigint' ? BigInt(bytes.toString().replace(/\..*$/, '')) : BigInt(IntVal(bytes));
    decimals = IntVal(decimals);

    const isMinus = bytes < BigInt(0);
    bytes = isMinus ? BigInt(bytes.toString().substring(1)) : bytes;

    const quanta : {
        [key: string]: number
    } = {
        // ========================= Origin ====
        'B' : 1,              // 1
        'KB' : 1024,           // pow( 1024, 1)
        'MB' : 1048576,        // pow( 1024, 2)
        'GB' : 1073741824,     // pow( 1024, 3)
        'TB' : 1099511627776,  // pow( 1024, 4)
        'PB' : 1125899906842624,  // pow( 1024, 5)
    };
    // const divBigInt = (x: bigint, y: bigint) : number => {
    //     return Number(BigInt(x) * BigInt(100) / BigInt(y)) / 100;
    // }
    const BigIntSafe = BigInt(Number.MAX_SAFE_INTEGER);
    decimals = Math.max(0, decimals);
    const compare = (a: number, b: number): number => a < b ? -1 : (a > b ? 1 : 0);
    let currentUnit = 'B';
    let currentDiv: number = bytes > BigIntSafe ? Number(bytes)/1024 : Number(bytes * BigInt(100) / BigInt(1024)) / 100;
    for (let unit in quanta) {
        const size = quanta[unit];
        if (compare(size, currentDiv) === 1) {
            bytes = Number(bytes)/size;
            currentUnit = unit;
            break;
        }
    }
    const fixed = parseFloat(bytes.toString()).toFixed(decimals).replace(/\.0+$/, '');
    return (isMinus ? '-' : '') + fixed + ' ' + currentUnit;
}

export const ParseDuration = (duration: number) : {
    years: number,
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
    milliseconds: number;
} => {

    let remain : number = duration;
    let years = Math.floor(remain / (1000 * 60 * 60 * 24 * 265));
    let days = Math.floor(remain / (1000 * 60 * 60 * 24))

    remain = remain % (1000 * 60 * 60 * 24)
    let hours = Math.floor(remain / (1000 * 60 * 60))

    remain = remain % (1000 * 60 * 60)
    let minutes = Math.floor(remain / (1000 * 60))
    remain = remain % (1000 * 60)

    let seconds = Math.floor(remain / (1000))
    remain = remain % (1000)

    let milliseconds = remain

    return {
        years,
        days,
        hours,
        minutes,
        seconds,
        milliseconds
    };
}

/**
 * Format Duration
 *
 * @param {number} duration
 * @param {?number} decimal
 * @constructor
 */
export const FormatDuration = (duration: number, decimal?:number) : string => {
    let {
        years,
        days,
        hours,
        minutes,
        seconds,
        milliseconds
    } = ParseDuration(duration);

    let result = '';
    if (years) {
        result += years + 'y ';
    }
    if (days) {
        result += days + 'd ';
    }
    if (hours) {
        result += hours + 'h ';
    }
    if (minutes) {
        result += minutes + 'm ';
    }
    if (seconds) {
        result += seconds + 's ';
    }
    if (milliseconds) {
        if (decimal && decimal > 0) {
            milliseconds = parseFloat(milliseconds.toFixed(decimal));
        } else {
            milliseconds = Math.round(milliseconds);
        }
        result += milliseconds + 'ms';
    }
    return result.trim();
}

/**
 * Format Duration HTML Element
 * @param duration
 * @param decimal
 * @constructor
 */
export const FormatDurationHtmlElement = (duration: number, decimal?:number) : string => {
    let {
        years,
        days,
        hours,
        minutes,
        seconds,
        milliseconds
    } = ParseDuration(duration);
    let result = '';
    if (years) {
        result += `<span class="formatted-years">
    <span class="formatted-number">${years}</span>
    <span class="formatted-label">y</span>
</span> `;
    }
    if (days) {
        result += `<span class="formatted-days">
    <span class="formatted-number">${days}</span>
    <span class="formatted-label">d</span>
</span> `;
    }
    if (hours) {
        result += `<span class="formatted-hours">
    <span class="formatted-number">${hours}</span>
    <span class="formatted-label">d</span>
</span> `;
    }
    if (minutes) {
        result += `<span class="formatted-minutes">
    <span class="formatted-number">${minutes}</span>
    <span class="formatted-label">d</span>
</span> `;
    }
    if (seconds) {
        result += `<span class="formatted-seconds">
    <span class="formatted-number">${seconds}</span>
    <span class="formatted-label">d</span>
</span> `;
    }
    if (milliseconds) {
        if (decimal && decimal > 0) {
            milliseconds = parseFloat(milliseconds.toFixed(decimal));
        } else {
            milliseconds = Math.round(milliseconds);
        }
        result += `<span class="formatted-milliseconds">
    <span class="formatted-number">${milliseconds.toString().replace(/0+$/, '').replace(/\.$/, '')}</span>
    <span class="formatted-label">ms</span>
</span> `;    }
    return result;
}