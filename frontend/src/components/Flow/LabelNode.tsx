import clsx from 'clsx';
import { Button } from 'flowbite-react';
import React, { memo, useState } from 'react';
import { Handle } from 'reactflow';
import { DeleteModal } from '../DeleteModal';

export default memo(({ data, id }: any) => {
    const [edit, setEdit] = useState(false)
    const [deleteOpen, setDeleteOpen] = useState(false)
//data.delete(id)
    const deleteLabel = () => {
        data.delete(id)
        setDeleteOpen(false)
    }
    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        setEdit(!edit)
    }
    return (
        <>
            <form className="group" onSubmit={submit}>
                {edit ? <input type="text" className="dark:text-black nodrag" value={data.label} onChange={(e) => data.onChange(e, id)} /> : <p>{data.label}</p>}
                <div className={clsx("group-hover:flex flex-wrap gap-2", !edit && 'hidden', edit && 'flex')}>
                    <Button className={clsx('nodrag', edit ? 'inline' : 'hidden')} type="submit">Save</Button>
                    <Button className={clsx('nodrag', edit ? 'hidden' : 'inline')} onClick={() => setEdit(!edit)}>Edit</Button>
                    <Button className='inline nodrag' color="failure" onClick={() => setDeleteOpen(true)}>Delete</Button>
                </div>
            </form>
            {deleteOpen && <DeleteModal isOpen={deleteOpen} close={() => setDeleteOpen(false)} confirm={deleteLabel} label={data.label}/>}
        </>
    );
});
