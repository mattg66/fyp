import clsx from 'clsx';
import { Button } from 'flowbite-react';
import React, { memo, useState } from 'react';
import { Handle } from 'reactflow';
import { DeleteModal } from '../DeleteModal';
import { EditModal } from './EditRackModal';

export default memo(({ data, id }: any) => {
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [editOpen, setEditOpen] = useState(false)
  //data.delete(id)
  const deleteLabel = () => {
    data.delete(id)
    setDeleteOpen(false)
  }
  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    setEditOpen(!editOpen)
  }
  return (
    <>
      <div className="group">
        <div className="border h-40 w-20 bg-white dark:bg-gray-800 flex items-center justify-center text-center">
          <p>{data.label}</p>
        </div>
        <div className='group-hover:flex flex-wrap gap-2 pt-2 hidden'>
          <Button className='nodrag inline' onClick={() => setEditOpen(!editOpen)}>Edit</Button>
          <Button className='inline nodrag' color="failure" onClick={() => setDeleteOpen(true)}>Delete</Button>
        </div>
      </div>
      {editOpen && <EditModal isOpen={editOpen} close={() => setEditOpen(false)} confirm={deleteLabel} data={data} id={id} />}
      {deleteOpen && <DeleteModal isOpen={deleteOpen} close={() => setDeleteOpen(false)} confirm={deleteLabel} label={data.label} />}
    </>
  );
});
