export const proposalKinds = ['golf','hotel','hotel-golf'];
export function nightsBetween(first,last){if(!first||!last)return 0;return Math.max(0,Math.round((Date.parse(last+'T00:00:00Z')-Date.parse(first+'T00:00:00Z'))/86400000));}
export function lineFactor(line){
    const people=Math.max(0,Number(line.pax)-Number(line.freePax));
    return line.basis==='PERSON'?people:line.basis==='PERSON_NIGHT'?people*nightsBetween(line.firstDate,line.lastDate):line.basis==='ROOM_NIGHT'?Number(line.rooms)*nightsBetween(line.firstDate,line.lastDate):1;
}
export function lineAmount(line,side){
    const value=line[side+'Price'];
    if(value===''||value===null||value===undefined)return null;
    const result=Number(value)*lineFactor(line)+Number(line[side+'Extras']||0);
    return Number.isFinite(result)?Math.round((result+Number.EPSILON)*100)/100:null;
}
// Different currencies are never silently added or converted.
export function proposalTotals(lines,side){const totals={};for(const line of lines){const currency=line[side+'Currency'];const amount=lineAmount(line,side);const item=totals[currency]??{amount:0,incomplete:false};if(amount===null)item.incomplete=true;else item.amount=Math.round((item.amount+amount)*100)/100;totals[currency]=item;}return totals;}
