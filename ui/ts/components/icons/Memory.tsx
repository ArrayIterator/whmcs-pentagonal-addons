import {ReactElement} from "react";

export default (props: object): ReactElement => {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 512 512">
            <path d="M0 151h15c8.52 0 16 7 16 15s-7.48 15-16 15H0v180h31v90h450v-90h31V181h-15c-8.52 0-16-7-16-15s7.48-15 16-15h15V61H0v90zm61 210h30v60H61v-60zm330 0v60h-30v-60h30zm-60 60h-30v-60h30v60zm-60 0h-30v-60h30v60zm-60 0h-30v-60h30v60zm-60 0h-30v-60h30v60zm300 0h-30v-60h30v60zM30 91h452v32.5c-17.45 6-31 22.51-31 42.5 0 19.92 13.5 36.48 31 42.5V331H30V208.5c17.45-6 31-22.51 31-42.5 0-19.92-13.5-36.48-31-42.5V91z"/>
            <path
                d="M181 121H91v120h90V121zm-30 90h-30v-60h30v60zM301 121h-90v120h90V121zm-30 90h-30v-60h30v60zM421 121h-90v120h90V121zm-30 90h-30v-60h30v60zM421 271h30v30h-30zM61 271h30v30H61zM241 271h30v30h-30z"/></svg>
    );
}
