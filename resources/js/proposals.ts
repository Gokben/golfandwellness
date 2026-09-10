import { useMysqlRecords } from './useMysqlRecords';
import { sourceProposals } from './proposalSource.mjs';
import { useAgencies } from './entities';
export type ProposalKind='golf'|'hotel'|'hotel-golf';
export type ProposalLine={id:string;category:ProposalKind;hotel:string;course:string;firstDate:string;lastDate:string;rounds:string;teeTime:string;pax:number;freePax:number;rooms:number;infants:number;children:number;board:string;roomType:string;roomName:string;accommodation:string;golfContract:string;hotelContract:string;packageId:string;tariffId:string;priceType:string;agencyPriceType:string;citizen:string;market:string;subMarket:string;freeStatus:string[];hotelAllotment:boolean;hotelGuarantee:boolean;agencyAllotment:boolean;agencyGuarantee:boolean;extraIds:string[];handling:string;transfer:string;basis:string;buyPrice:string;sellPrice:string;buyCurrency:string;sellCurrency:string;buyExtras:string;sellExtras:string;note:string};
export type Proposal={id:string;title:string;createDate:string;optionDate:string;agency:string;creator:string;status:string;note:string;lines:ProposalLine[];sourceTables?:string[][][];sourceId?:string};
export const proposalStatuses=['OPTIONAL','CONFIRMED','CHANGED','CANCELLED'];
export const today=()=>new Date().toLocaleDateString('sv-SE');
export function newProposal(creator:string):Proposal{return {id:crypto.randomUUID(),title:'',createDate:today(),optionDate:'',agency:'',creator,status:'OPTIONAL',note:'',lines:[]};}
export function newProposalLine(category:ProposalKind):ProposalLine{return {id:crypto.randomUUID(),category,hotel:'',course:'',firstDate:'',lastDate:'',rounds:'1',teeTime:'',pax:1,freePax:0,rooms:1,infants:0,children:0,board:'',roomType:'',roomName:'',accommodation:'DBL',golfContract:'',hotelContract:'',packageId:'',tariffId:'',priceType:'TOHG',agencyPriceType:'TOHG',citizen:'',market:'',subMarket:'',freeStatus:[],hotelAllotment:false,hotelGuarantee:false,agencyAllotment:false,agencyGuarantee:false,extraIds:[],handling:'',transfer:'',basis:category==='golf'?'PERSON':category==='hotel'?'ROOM_NIGHT':'PERSON',buyPrice:'',sellPrice:'',buyCurrency:'EUR',sellCurrency:'EUR',buyExtras:'0',sellExtras:'0',note:''};}
export function useProposals(kind:ProposalKind){
    const agencies=useAgencies();
    return useMysqlRecords<Proposal>('proposals-'+kind,sourceProposals[kind],(v:unknown):v is Proposal=>{const r=v as Proposal;return !!r&&typeof r.id==='string'&&typeof r.title==='string'&&Array.isArray(r.lines);},undefined,async()=>{
        await agencies.initialized;
        if(!agencies.ready.value) throw new Error(agencies.storageError.value || 'Teklifler için acente kayıtları yüklenemedi.');
    });
}
