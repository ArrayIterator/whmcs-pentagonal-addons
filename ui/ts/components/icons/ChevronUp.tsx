import {ReactElement} from "react";

export default (props: object) : ReactElement => {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" style={{
            fill: "none!important"
        }} fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor"
             className="size-6">
            <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/>
        </svg>
    );
}