import {AddonUriDefinition} from "../../types/definitions";
import React, {ReactElement} from "react";
import {FormatDurationHtmlElement, FormatFileSize, FormatDuration} from "../../utils/formatter";
import Bug from "../icons/Bug";
import Memory from "../icons/Memory";
import Clock from "../icons/Clock";
import ChevronUp from "../icons/ChevronUp";
import ChevronDown from "../icons/ChevronDown";
import Expand from "../icons/Expand";
import Collapse from "../icons/Collapse";
import Close from "../icons/Close";

type JsonDebugBar = {
    "memory": {
        "start": number;
        "end": number;
        "usage": number;
        "peak_start": number;
        "peak_end": number;
    };
    "time": {
        "start": number;
        "end": number;
        "usage": number;
    };
    records: Array<{
        "group": string;
        "name": string;
        "memory": {
            "start": number;
            "end": number;
            "usage": number;
        };
        "time": {
            "start": number;
            "end": number;
            "usage": number;
        };
        data: {
            [key: string]: any;
        }
    }>
}
const calculatePercentage = (start: number, end: number, total: number) : number => {
    return Math.round(((end - start) / total) * 100);
};
const determineBarType = (time: number) : string => {
    const deflated : {
        [key: number]: string;
    } = {
        10: 'success',
        25: 'info',
        75: 'warning',
        150: 'danger'
    };
    for (const key in deflated) {
        if (time <= parseInt(key)) {
            return deflated[key];
        }
    }
    return 'pentagonal-debug-record-time-info';
}
export default function DebugBar({addonUri, jsonDebugBar} : {
    addonUri: AddonUriDefinition;
    jsonDebugBar: JsonDebugBar;
}) : ReactElement {
    const groups : {
        [key: string]: number;
    } = {};
    let increment = 0;
    let total = jsonDebugBar.time.usage;
    // sort record by started time
    jsonDebugBar.records = jsonDebugBar.records.sort(function(a:{
        time: {
            start: number
        }
    }, b: {
        time: {
            start: number
        }
    }) {
        return a.time.start - b.time.start;
    });
    for (const record of jsonDebugBar.records) {
        if (!groups[record.group]) {
            groups[record.group] = increment++;
        }
    }
    return (
        <div className={"pentagonal-debug-bar-wrapper"}>
            <div className={"pentagonal-debug-spacer"}></div>
            <div className={"pentagonal-debug-header"}>
                <div className={"pentagonal-debug-header-title"} title={"Pentagonal Debug Bar"}>
                    {
                        Bug({
                            className: "pentagonal-debug-header-icon",
                            width: 30,
                            height: 30
                        })
                    }
                </div>
                <div className={"pentagonal-debug-header-info"}>
                    <div className={"pentagonal-debug-header-info-item"} title={`Memory Usage: ${FormatFileSize(jsonDebugBar.memory.usage, 2)}`}>
                        {
                            Memory({
                                className: "pentagonal-debug-header-icon",
                                width: 30,
                                height: 30
                            })
                        }
                        <div className={"pentagonal-debug-header-info-item-value"}>{FormatFileSize(jsonDebugBar.memory.usage, 2)}</div>
                    </div>
                    <div className={"pentagonal-debug-header-info-item"} title={`Peak Memory Usage: ${FormatFileSize(jsonDebugBar.memory.peak_end, 2)}`}>
                        {
                            Memory({
                                className: "pentagonal-debug-header-icon",
                                width: 30,
                                height: 30
                            })
                        }
                        <div className={"pentagonal-debug-header-info-item-value"}>{FormatFileSize(jsonDebugBar.memory.peak_end, 2)}</div>
                    </div>
                    <div className={"pentagonal-debug-header-info-item"} title={`Time Usage: ${FormatDuration(jsonDebugBar.time.usage, 2)}`}>
                        {
                            Clock({
                                className: "pentagonal-debug-header-icon",
                                width: 30,
                                height: 30
                            })
                        }
                        <div className={"pentagonal-debug-header-info-item-value"} dangerouslySetInnerHTML={{
                            __html: FormatDurationHtmlElement(jsonDebugBar.time.usage, 2)
                        }}></div>
                    </div>
                </div>
                <div className={"pentagonal-debug-header-action"}>
                    <div className={"pentagonal-debug-bar-action-item action-maximize"} title={"maximize"}>
                        {
                            Expand({
                                className: "pentagonal-debug-header-icon",
                                width: 30,
                                height: 30
                            })
                        }
                    </div>
                    <div className={"pentagonal-debug-bar-action-item action-minimize"} title={"minize"}>
                        {
                            Collapse({
                                className: "pentagonal-debug-header-icon",
                                width: 30,
                                height: 30
                            })
                        }
                    </div>
                    <div className={"pentagonal-debug-bar-action-item action-up"} title={"open"}>
                        {
                            ChevronUp({
                                className: "pentagonal-debug-header-icon",
                                width: 30,
                                height: 30
                            })
                        }
                    </div>
                    <div className={"pentagonal-debug-bar-action-item action-down"} title={"close"}>
                        {
                            Close({
                                className: "pentagonal-debug-header-icon",
                                width: 30,
                                height: 30
                            })
                        }
                    </div>
                </div>
            </div>
            <div className={"pentagonal-debug-bar-content"}>
                <div className={"pentagonal-debug-record-header"}>
                    <div className={"pentagonal-debug-record-header-search"}>
                        <input type={"search"} placeholder={"Search"} aria-label={"search"}/>
                    </div>
                    <div className={"pentagonal-debug-record-header-container"}>
                        <div className={"pentagonal-debug-record-group"}>Group</div>
                        <div className={"pentagonal-debug-record-name"}>Name</div>
                        <div className={"pentagonal-debug-record-usage"}>Usage</div>
                        <div className={"pentagonal-debug-record-time"}>Time</div>
                    </div>
                </div>
                <div className={"pentagonal-debug-record-items"}>
                    {jsonDebugBar.records.map((record, index) => {
                        const dataObject = {
                            "data-group-index": groups[record.group],
                            "data-memory-start": record.memory.start,
                            "data-memory-end": record.memory.end,
                            "data-memory-usage": record.memory.usage,
                            "data-time-start": record.time.start,
                            "data-time-end": record.time.end,
                            "data-time-usage": record.time.usage
                        };
                        let timeHtml = FormatDurationHtmlElement(record.time.usage, 3);
                        let duration = FormatDuration(record.time.usage, 3);
                        // const percentage = calculatePercentage(record.time.start, record.time.end, total);
                        const propAttr = {
                            "data-status-bar" : determineBarType(record.time.usage),
                        };
                        // const left = $startTime - $profilerStartTime) / $totalDuration * 100;
                        const percentage = (record.time.usage / total * 100).toFixed(3);
                        const left = ((record.time.start - jsonDebugBar.time.start) / total * 100).toFixed(3);
                        return (
                            <div {...dataObject} key={index} className={"pentagonal-debug-record"}>
                                <div className={"pentagonal-debug-record-log"}>
                                    <div className={"pentagonal-debug-record-group"} title={record.group}>{record.group}</div>
                                    <div className={"pentagonal-debug-record-name"}  title={record.name}>{record.name}</div>
                                    <div className={"pentagonal-debug-record-usage"} title={duration}
                                        dangerouslySetInnerHTML={{
                                            __html: timeHtml
                                        }}
                                    ></div>
                                    <div className={"pentagonal-debug-record-time"} title={`Time Usage: ${duration}`} {...{
                                        "data-percentage": percentage
                                    }}>
                                        <div className={"pentagonal-debug-record-time-bar"} style={{
                                            width: percentage + "%",
                                            left: left + "%"
                                        }} {...propAttr}>
                                        </div>
                                    </div>
                                </div>
                                <div className={"pentagonal-debug-record-data"}>
                                    <pre>{JSON.stringify(record.data, null, 4)}</pre>
                                </div>
                            </div>
                        )
                    })}
                </div>
            </div>
        </div>
    );
}