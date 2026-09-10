export function expandHotelGolfPackages(source) {
    return source.parents.map(parent=>({
        id:'kirpii-hgp-'+parent.id,name:parent.name,code:parent.code,
        children:source.courses.flatMap((course,index)=>Array.from({length:8},(_,offset)=>({
            id:String(parent.firstChildId-index*8+offset),
            name:offset===7?'UNLIMITED':`${offset+1} ROUND`,
            code:offset===7?'UNL':String(offset+1),courseKey:course.key,
        }))),
    }));
}
