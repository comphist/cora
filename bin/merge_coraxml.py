#!/usr/bin/python
# coding=utf-8

'''
author: Aiko Freyth
Last Update: 19-October-2014

'''

from __future__ import division

import os
import argparse
import nltk
import re
from lxml import etree
from tempfile import NamedTemporaryFile
from diffMatcher import DiffMatcher

description = "Merges two CorA XML files based on their modern tokenization."
epilog = "The output is a CorA XML file identical to TRANS_XML, but enriched with annotations from the parallel file ANNO_XML."
parser = argparse.ArgumentParser(description=description, epilog=epilog)
parser.add_argument('fileA',
                    metavar='TRANS_XML',
                    type=argparse.FileType('r'),
                    help='XML file with the definitive transcription')
parser.add_argument('fileB',
                    metavar='ANNO_XML',
                    type=argparse.FileType('r'),
                    help='XML file with the definitive annotation')
parser.add_argument('-i', '--ignore',
                    action='store_true',
                    help='If on, then script ignores case-sensitivity')
parser.add_argument('-l', '--log',
                    dest='logfile',
                    metavar='LOGFILE',
                    type=argparse.FileType('w'),
                    help='Write logfile of changes to LOGFILE')

args = parser.parse_args()

log_file = args.logfile
diff_list = []
complete_list = {}
seen = {}

def print_utf8(handle, text):
    try:
        print >> handle, text.encode("utf-8")
    except UnicodeError:
        print >> handle, text

#function reads out ascii data from given file and write them in an output file        
def read_out(root,file_out):
    for mod in root.iter('mod'):
        print_utf8(file_out, mod.attrib['ascii'])

def change_data(number,column, create_log):
    word = diff_list[number][column]
    look = 'token/mod[@ascii="'+word+'"]'
    result_list = []
    #if word is more than one time in file
    if word in seen:
        seen[word]+=1
        #create list for every match with the searched word
        result_list = complete_list[word]
        #take right word
        actual_word = result_list[seen[word]]
        id_b = actual_word.get("id")
        #take sub elements and put them in file A
        if create_log == 'no':
            for match in actual_word.iterfind(".//"):
                etree.SubElement(mod_a, match.tag, match.attrib)
    
    #if word is for the first time in file    
    else:
        #take only first word
        seen[word]=0
        if word not in complete_list:
            #look = 'token/mod[@ascii="'+word+'"]'
            complete_list[word] = root_b.findall(look)
            result_list = complete_list[word]
            
        actual_word = result_list[seen[word]]
        id_b = actual_word.get("id")
        if create_log == 'no':
            for match in actual_word.iterfind(".//"):
                etree.SubElement(mod_a, match.tag, match.attrib)
            
    return id_b

file_a = etree.parse(args.fileA)
root_a = file_a.getroot()
file_b = etree.parse(args.fileB)
root_b = file_b.getroot()
   
listA = NamedTemporaryFile(delete=False)
listB = NamedTemporaryFile(delete=False)
read_out(root_a,listA)
read_out(root_b,listB)
listA.close()
listB.close()

new_list = DiffMatcher(listA, listB)
diff_list = new_list.create_diff(listA, listB,args.ignore)

os.unlink(listA.name)
os.unlink(listB.name)

counter = -1

#calculates the ratio of 2 words
def calculate_ratio(word1,word2):
    lensum = len(word1)+len(word2)
    levdis = nltk.edit_distance(word1, word2)
    
    nltkratio = ((lensum-levdis)/lensum)
    
    return nltkratio

def make_log(type,id_a,b,counter):
    if type == 'DEL':
        print_utf8(log_file, 'DEL: \t'+diff_list[counter][1]+' ['+diff_list[counter+1][1]+' -> [] '+diff_list[counter+1][0]+'\t'+b)
    elif type == 'MATCH':
        print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter][2]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+1][0]+'\t'+b+' > '+id_a)
    elif type == 'INS':
        print_utf8(log_file, 'INS: \t'+diff_list[counter-1][0]+' [ -> '+diff_list[counter][0]+' ] '+diff_list[counter+1][0]+'\t'+id_a)

#case ">"
def delete(counter):
    if len(diff_list[counter-1]) == 3:
        r1 = calculate_ratio(diff_list[counter][1], diff_list[counter-1][0])
    else:
        r1 = 0.0
    if diff_list[counter-1][0] == ">" and len(diff_list[counter-2]) == 3:
        r2 = calculate_ratio(diff_list[counter][1], diff_list[counter-2][0])
    else:
        r2 = 0.0
    if diff_list[counter-1][0] == ">" and diff_list[counter-2][0] == ">" and len(diff_list[counter-3]) == 3:
        r3 = calculate_ratio(diff_list[counter][1], diff_list[counter-3][0])
    else:
        r3 = 0.0
    
    #if all values are zero, no match is found -> delete
    if r1 == 0.0 and r2 == 0.0 and r3 == 0.0:
        b = change_data(counter,1, 'yes')
        make_log('DEL',0,b,counter)
        
    

#case 3 words in line
def difference(counter):
    #if in next line no other possibility, merge this words
    if diff_list[counter+1][0] != ">" and diff_list[counter+1][1] != "<" and len(diff_list[counter+1]) == 2:
        b = change_data(counter, 2, 'no')
        make_log('MATCH',id_a,b,counter)
       
    else:
        #in next line there is also a difference -> 3 words in line
        # word1 | word2
        # word3 | word4
        if len(diff_list[counter+1]) == 3:
            r = []
            r.append(calculate_ratio(diff_list[counter][0], diff_list[counter][2]))
            r.append(calculate_ratio(diff_list[counter+1][0], diff_list[counter][2]))
            if r[0] > r[1]:
                b = change_data(counter,2, 'no')
                make_log('MATCH',id_a,b,counter)
            
        #in next line other possibility
        elif diff_list[counter+1][0] == ">":
            r = []
            r.append(calculate_ratio(diff_list[counter][0], diff_list[counter][2]))
            r.append(calculate_ratio(diff_list[counter][0], diff_list[counter+1][1]))
            if diff_list[counter+2][0] == ">":
                r.append(calculate_ratio(diff_list[counter][0], diff_list[counter+2][1]))
                if diff_list[counter+3][0] == ">":
                    r.append(calculate_ratio(diff_list[counter][0], diff_list[counter+3][1]))
            max_value = max(r)
            c = 0
            #find best ratio
            for i in r:
                if r[c] == max_value:
                    tmp = c
                c+=1
            
                
            #make log and merge
            if tmp == 0:
                b = change_data(counter,2, 'no')
                make_log('MATCH',id_a,b,counter)
                number = re.search(r'\d+', b).group()
                new_b = b.replace(number,str(int(number)+1))
                print_utf8(log_file, 'DEL: \t'+diff_list[counter][0]+' ['+diff_list[counter+1][1]+' -> ] '+diff_list[counter+2][0]+'\t'+new_b)
            elif tmp == 1:
                b = change_data(counter+tmp,1, 'no')
                number = re.search(r'\d+', b).group()
                new_b = b.replace(number,str(int(number)-1))
                print_utf8(log_file, 'DEL: \t'+diff_list[counter][0]+' ['+diff_list[counter][2]+' -> ] '+diff_list[counter+2][0]+'\t'+new_b)
                print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter+tmp][1]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+tmp+1][0]+'\t'+b+' > '+id_a)
            elif tmp == 2:
                b = change_data(counter+tmp,1, 'no')
                number = re.search(r'\d+', b).group()
                new_b = b.replace(number,str(int(number)-2))
                print_utf8(log_file, 'DEL: \t'+diff_list[counter][0]+' ['+diff_list[counter][2]+' -> ] '+diff_list[counter+tmp+1][0]+'\t'+new_b)
                new_b = b.replace(number,str(int(number)-1))
                print_utf8(log_file, 'DEL: \t'+diff_list[counter][0]+' ['+diff_list[counter+1][1]+' -> ] '+diff_list[counter+tmp+1][0]+'\t'+new_b)
                print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter+tmp][1]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+tmp+1][0]+'\t'+b+' > '+id_a)
            elif tmp == 3:
                b = change_data(counter+tmp,1, 'no')
                number = re.search(r'\d+', b).group()
                new_b = b.replace(number,str(int(number)-3))
                print_utf8(log_file, 'DEL: \t'+diff_list[counter][0]+' ['+diff_list[counter][2]+' -> ] '+diff_list[counter++tmp+1][0]+'\t'+new_b)
                new_b = b.replace(number,str(int(number)-2))
                print_utf8(log_file, 'DEL: \t'+diff_list[counter][0]+' ['+diff_list[counter+1][1]+' -> ] '+diff_list[counter+tmp+2][0]+'\t'+new_b)
                new_b = b.replace(number,str(int(number)-1))
                print_utf8(log_file, 'DEL: \t'+diff_list[counter][0]+' ['+diff_list[counter+2][1]+' -> ] '+diff_list[counter+tmp+3][0]+'\t'+new_b)
                print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter+tmp][1]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+tmp+1][0]+'\t'+b+' > '+id_a)
                
        
        #other possibility    
        elif diff_list[counter+1][1] == "<":
            #calculate all possible ratios
            r = []
            max_tmp = calculate_ratio(diff_list[counter][0], diff_list[counter][2])
            r.append(calculate_ratio(diff_list[counter+1][0], diff_list[counter][2]))
            if diff_list[counter+2][1] == "<":
                r.append(calculate_ratio(diff_list[counter+2][0], diff_list[counter][2]))
                if diff_list[counter+3][1] == "<":
                    r.append(calculate_ratio(diff_list[counter+3][0], diff_list[counter][2]))
            max_value = max(r)
            #if current one is the best: merge and create log
            if max_value < max_tmp:
                b = change_data(counter, 2, 'no')
                make_log('MATCH',id_a,b,counter)
                ##########################
            else:
                print_utf8(log_file, 'INS: \t'+diff_list[counter-1][0]+' [ -> '+diff_list[counter][0]+' ] '+diff_list[counter+1][0]+'\t'+id_a)
                
            if len(diff_list[counter-1]) == 3:
                #first check the line with difference with line before
                value_1 = calculate_ratio(diff_list[counter-1][0], diff_list[counter-1][2])
                value_2 = calculate_ratio(diff_list[counter][0], diff_list[counter-1][2])
                if value_1 < value_2:
                    b = change_data(counter-1,2, 'no')
                    print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter-1][2]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+1][0]+'\t'+b+' > '+id_a)
                
        
        elif len(diff_list[counter-1]) == 3:
            value_1 = calculate_ratio(diff_list[counter-1][0], diff_list[counter-1][2])
            value_2 = calculate_ratio(diff_list[counter][0], diff_list[counter-1][2])
            if value_1 < value_2:
                b = change_data(counter-1,2, 'no')
                print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter-1][2]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+1][0]+'\t'+b+' > '+id_a)

#case "<"                    
def insert(counter):
    #line before has 3 entries
    if len(diff_list[counter-1]) == 3:
        #calculate ratios
        r_a = calculate_ratio(diff_list[counter-1][0], diff_list[counter-1][2])
        r1 = calculate_ratio(diff_list[counter][0], diff_list[counter-1][2])
        if diff_list[counter+1][1] == "<":
            r2 = calculate_ratio(diff_list[counter+1][0], diff_list[counter-1][2])
        else:
            r2 = 0.0
        if diff_list[counter+2][1] == "<":
            r3 = calculate_ratio(diff_list[counter+2][0], diff_list[counter-1][2])
        else:
            r3 = 0.0
        
        #ratio of first word in line is best    
        if r1 > r_a and r1 > r2 and r1 > r3:
            b = change_data(counter-1, 2, 'no')
            print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter-1][2]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+1][0]+'\t'+b+' > '+id_a)
        else:
            make_log('INS',id_a,0,counter)
            
    
    #Word1 | Word2
    #Word3 <
    #Word4 <  
    elif diff_list[counter-1][1] == "<" and len(diff_list[counter-2]) == 3:
        r_a = calculate_ratio(diff_list[counter-2][0], diff_list[counter-2][2])
        r1 = calculate_ratio(diff_list[counter-1][0], diff_list[counter-2][2])
        r2 = calculate_ratio(diff_list[counter][0], diff_list[counter-2][2])
        if diff_list[counter+1][1] == "<":
            r3 = calculate_ratio(diff_list[counter+1][0], diff_list[counter-2][2])
        else:
            r3 = 0.0
        #ratio of Word4 and Word2 is best
        if r2 > r_a and r2 > r1 and r2 > r3:
            b = change_data(counter-2, 2, 'no')
            print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter-2][2]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+1][0]+'\t'+b+' > '+id_a)
        else:
            make_log('INS',id_a,0,counter)
    
    #Word1 | Word2
    #Word3 <
    #Word4 <
    #Word5 <
    elif diff_list[counter-1][1] == "<" and diff_list[counter-2][1] == "<" and len(diff_list[counter-3]) == 3:
        r_a = calculate_ratio(diff_list[counter-3][0], diff_list[counter-3][2])
        r1 = calculate_ratio(diff_list[counter-1][0], diff_list[counter-3][2])
        r2 = calculate_ratio(diff_list[counter-2][0], diff_list[counter-3][2])
        r3 = calculate_ratio(diff_list[counter][0], diff_list[counter-3][2])
        #ratio of Word5 and Word2 is best
        if r3 > r_a and r3 > r1 and r3 > r2:
            b = change_data(counter-3, 2, 'no')
            print_utf8(log_file, 'MATCH: \t'+diff_list[counter-1][0]+' ['+diff_list[counter-3][2]+' -> '+diff_list[counter][0]+'] '+diff_list[counter+1][0]+'\t'+b+' > '+id_a)
        else:
            make_log('INS',id_a,0,counter)
    
    else:
        if counter == 0:
            print_utf8(log_file, 'INS: \t [ -> '+diff_list[counter][0]+' ] '+diff_list[counter+1][0]+'\t'+id_a)
        elif counter < (len(diff_list)-1):
            make_log('INS',id_a,0,counter)
        else:
            print_utf8(log_file, 'INS: \t'+diff_list[counter-1][0]+' [ -> '+diff_list[counter][0]+' ] \t'+id_a)
                        
#function for process of one line in list of first file
def treat_of_mod(c,id_a):
    counter = c+1
    #case: Only connect if two asciis are similar
    #now this way, because of case-sensitive
    if diff_list[counter][0] != ">" and diff_list[counter][1] != "<" and len(diff_list[counter]) == 2:
    #if diff_list[counter][0] == diff_list[counter][1]:
        b = change_data(counter, 1, 'no')
        
    #case: Only in 2nd file
    elif diff_list[counter][0] == ">":
        delete(counter)
        counter = treat_of_mod(counter,id_a)
    
    #case: difference in line       
    elif len(diff_list[counter]) == 3:
        difference(counter)
    
    #case: Only in first file
    elif diff_list[counter][1] == "<":
        insert(counter)
       
    return counter

#run through every mod in first file
for mod_a in file_a.iter("mod"):
    id_a = mod_a.get("id")
    #treatment for every mod
    counter = treat_of_mod(counter,id_a)
    

print_utf8(log_file, '\nmerge completed')
log_file.close()
print (etree.tostring(root_a, encoding="utf-8", xml_declaration=True))