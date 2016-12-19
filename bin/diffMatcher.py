#!/usr/bin/python
# coding=utf-8

import subprocess

class DiffMatcher(object):
    
    def __init__(self, listA, listB):
        self.listA = listA
        self.listB = listB


    def create_diff(self, listA, listB,case_sensitive):
        new_list = []
        #compare the two files
        try:
            if (case_sensitive):
                #ignore case sensitiveness
                inp = subprocess.check_output(['diff', '-iy', listA.name, listB.name])
            else:
                inp = subprocess.check_output(['diff', '-y', listA.name, listB.name])
        # diff exits with 1 if outputs mismatch... grml
        except subprocess.CalledProcessError, e: 
            inp = e.output

            
        inp = inp.decode("utf-8").split("\n")
        
        #create list of difference
        for entry in inp:
            g = entry.replace("\t"," ")
            g = g.split()
            new_list.append(g)
            
        
        del new_list[-1]
        
        return new_list
        