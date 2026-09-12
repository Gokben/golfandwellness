export type ReservationPerson = { id:string; title:string; name:string; age:string; birthDate:string; roomType:string; transfer:string };
export const newReservationPerson = ():ReservationPerson => ({ id:crypto.randomUUID(), title:'MR', name:'', age:'', birthDate:'', roomType:'', transfer:'' });
export const hasPersonDetails = (person:ReservationPerson) => [person.name, person.age, person.birthDate, person.roomType, person.transfer].some(value => String(value ?? '').trim() !== '') || person.title !== 'MR';
