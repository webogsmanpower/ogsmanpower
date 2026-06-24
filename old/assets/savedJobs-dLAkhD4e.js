import{d as e}from"./index-BFWN3IEs.js";const t=async(s={})=>(await e.get("/seeker/saved-jobs",{params:s})).data,n=async s=>(await e.post(`/jobs/${s}/save`)).data;export{t as g,n as t};
