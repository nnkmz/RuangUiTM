import {test}from'node:test';import assert from'node:assert/strict';import{parseCSV,normalizeRows,exportCSV}from'../roomImport.js';
const r={building:'AKD',floor:'01',code:'T01',name:'Pejabat, "Utama"\nAras 1',type:'Pejabat',category:'OCCUPIABLE',department:'Pentadbiran',manual:12,capacity:2,ac:true,accessible:false,points:[[0,0],[4,0],[4,3],[0,3]]};
test('CSV quoted comma, quote, newline and polygon survive roundtrip',()=>{const saved=normalizeRows(parseCSV(exportCSV(normalizeRows([r]))))[0];assert.equal(saved.name,r.name);assert.deepEqual(saved.points,r.points);assert.equal(saved.manual,12);assert.equal(saved.ac,true)});
test('Malformed quoting rejected',()=>assert.throws(()=>parseCSV('a,b\n"unfinished,x')));
test('Duplicate headers and wrong field counts rejected',()=>{assert.throws(()=>parseCSV('a,a\nx,y'));assert.throws(()=>parseCSV('a,b\nx'))});
test('Invalid numbers, booleans and points rejected',()=>{assert.throws(()=>normalizeRows([{...r,manual:'NaN'}]));assert.throws(()=>normalizeRows([{...r,ac:'maybe'}]));assert.throws(()=>normalizeRows([{...r,points:'bad json'}]))});
test('Missing required identity and oversized batches rejected',()=>{assert.throws(()=>normalizeRows([{...r,name:''}]));assert.throws(()=>normalizeRows(Array.from({length:101},()=>r)))});
