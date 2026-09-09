import { useMysqlRecords } from './useMysqlRecords';
import source from './hotelGolfPackageSource.json';
import { expandHotelGolfPackages } from './hotelGolfPackageSeed.mjs';
export type PackageDefinitionChild = { id:string; name:string; code:string; courseKey:string };
export type PackageDefinition = { id:string; name:string; code:string; children:PackageDefinitionChild[] };
export const packageDefinitionDefaults:PackageDefinition[]=expandHotelGolfPackages(source);
const text=(v:unknown)=>typeof v==='string'&&v.trim().length>0;
export function validPackageDefinition(value:unknown):value is PackageDefinition {
    const row=value as PackageDefinition;
    return !!row&&typeof row==='object'&&text(row.id)&&text(row.name)&&text(row.code)&&Array.isArray(row.children)
        &&row.children.every(c=>c&&text(c.id)&&text(c.name)&&text(c.code)&&text(c.courseKey));
}
export function usePackageDefinitions(){return useMysqlRecords('hotel-golf-package-definitions',packageDefinitionDefaults,validPackageDefinition);}
